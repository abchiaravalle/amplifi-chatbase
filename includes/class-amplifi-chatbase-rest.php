<?php
/**
 * REST proxy: relays chat messages to Chatbase so the secret key never reaches the browser.
 *
 * @package Amplifi_Chatbase
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and serves the chat proxy endpoint.
 */
class Amplifi_Chatbase_Rest {

	const NAMESPACE = 'amplifi-chatbase/v1';

	/**
	 * Settings handler.
	 *
	 * @var Amplifi_Chatbase_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Amplifi_Chatbase_Settings $settings Settings.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/chat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => array( $this, 'check_nonce' ),
				'args'                => array(
					'messages' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);
	}

	/**
	 * Verify the front-end nonce to block cross-site abuse.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_nonce( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'amplifi_cb_forbidden', __( 'Invalid session token.', 'amplifi-chatbase' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Handle a chat request: proxy to Chatbase, streaming when enabled.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|void
	 */
	public function handle_chat( $request ) {
		$opts = $this->settings->get();

		if ( empty( $opts['api_key'] ) || empty( $opts['chatbot_id'] ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Chatbot is not configured.', 'amplifi-chatbase' ) ),
				500
			);
		}

		$messages = $request->get_param( 'messages' );
		$messages = $this->sanitize_messages( $messages );

		if ( empty( $messages ) ) {
			return new WP_REST_Response(
				array( 'error' => __( 'No message provided.', 'amplifi-chatbase' ) ),
				400
			);
		}

		// Basic per-IP throttle to deter abuse of the proxy.
		if ( ! $this->rate_ok() ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Too many requests. Please slow down.', 'amplifi-chatbase' ) ),
				429
			);
		}

		$stream  = ! empty( $opts['stream'] );
		$payload = array(
			'messages'         => $messages,
			'chatbotId'        => $opts['chatbot_id'],
			'stream'           => $stream,
			'temperature'      => 0,
		);

		$endpoint = trailingslashit( $opts['api_base'] ) . 'api/v1/chat';

		if ( $stream ) {
			$this->stream_response( $endpoint, $opts['api_key'], $payload );
			return; // Output already flushed.
		}

		return $this->buffered_response( $endpoint, $opts['api_key'], $payload );
	}

	/**
	 * Non-streaming request: fetch the whole answer then return JSON.
	 *
	 * @param string $endpoint URL.
	 * @param string $key      API key.
	 * @param array  $payload  Body.
	 * @return WP_REST_Response
	 */
	private function buffered_response( $endpoint, $key, $payload ) {
		$resp = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 45,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $key,
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $resp ) ) {
			return new WP_REST_Response( array( 'error' => $resp->get_error_message() ), 502 );
		}

		$code = wp_remote_retrieve_response_code( $resp );
		$body = wp_remote_retrieve_body( $resp );

		if ( $code < 200 || $code >= 300 ) {
			return new WP_REST_Response( array( 'error' => $this->extract_error( $body ) ), $code );
		}

		$data = json_decode( $body, true );
		$text = '';
		if ( isset( $data['text'] ) ) {
			$text = $data['text'];
		} elseif ( isset( $data['messages'] ) && is_array( $data['messages'] ) ) {
			$last = end( $data['messages'] );
			$text = isset( $last['content'] ) ? $last['content'] : '';
		}

		return new WP_REST_Response( array( 'text' => $text ), 200 );
	}

	/**
	 * Streaming request: pipe Chatbase's chunked body straight to the browser as SSE.
	 *
	 * @param string $endpoint URL.
	 * @param string $key      API key.
	 * @param array  $payload  Body.
	 */
	private function stream_response( $endpoint, $key, $payload ) {
		// Prepare a clean output buffer for SSE.
		if ( function_exists( 'ob_get_level' ) ) {
			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
		}

		nocache_headers();
		header( 'Content-Type: text/event-stream; charset=utf-8' );
		header( 'X-Accel-Buffering: no' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );

		$emit = function ( $type, $value ) {
			echo 'data: ' . wp_json_encode( array( $type => $value ) ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( function_exists( 'flush' ) ) {
				flush();
			}
		};

		// Shared state so the write callback can branch on the upstream status
		// and buffer an error body instead of leaking it as bot text.
		$state = array(
			'code'     => 0,
			'err_body' => '',
		);

		$ch = curl_init( $endpoint ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init

		curl_setopt_array( // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt_array
			$ch,
			array(
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => wp_json_encode( $payload ),
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: application/json',
					'Authorization: Bearer ' . $key,
				),
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_TIMEOUT        => 120,
				CURLOPT_WRITEFUNCTION  => function ( $handle, $chunk ) use ( $emit, &$state ) {
					// Resolve the upstream status once (headers are parsed before the body).
					if ( 0 === $state['code'] ) {
						$state['code'] = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo
					}
					if ( $state['code'] >= 400 ) {
						// Buffer the error body; it is delivered as a clean error event at the end.
						$state['err_body'] .= $chunk;
					} elseif ( '' !== $chunk ) {
						// Healthy stream: forward each raw text chunk as an SSE text event.
						$emit( 'text', $chunk );
					}
					return strlen( $chunk );
				},
			)
		);

		$ok = curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec

		if ( false === $ok ) {
			$err = curl_error( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error
			$emit( 'error', $err ? $err : __( 'Stream failed.', 'amplifi-chatbase' ) );
		} elseif ( $state['code'] >= 400 ) {
			// Upstream returned an error: surface a clean message, not the raw body.
			$emit( 'error', $this->extract_error( $state['err_body'] ) );
		}

		curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close

		echo "event: done\ndata: {}\n\n";
		if ( function_exists( 'flush' ) ) {
			flush();
		}
		exit;
	}

	/**
	 * Sanitize the incoming message array into Chatbase's expected shape.
	 *
	 * @param mixed $messages Raw.
	 * @return array
	 */
	private function sanitize_messages( $messages ) {
		if ( ! is_array( $messages ) ) {
			return array();
		}
		$clean = array();
		foreach ( $messages as $m ) {
			if ( ! is_array( $m ) || empty( $m['content'] ) ) {
				continue;
			}
			$role = isset( $m['role'] ) && in_array( $m['role'], array( 'user', 'assistant' ), true ) ? $m['role'] : 'user';
			$content = sanitize_textarea_field( wp_unslash( $m['content'] ) );
			$content = mb_substr( $content, 0, 4000 );
			if ( '' === $content ) {
				continue;
			}
			$clean[] = array(
				'role'    => $role,
				'content' => $content,
			);
		}
		// Keep the tail of the conversation only.
		if ( count( $clean ) > 30 ) {
			$clean = array_slice( $clean, -30 );
		}
		return $clean;
	}

	/**
	 * Simple per-IP rate limiter using transients (20 requests / minute).
	 *
	 * @return bool
	 */
	private function rate_ok() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'amplifi_cb_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 20 ) {
			return false;
		}
		set_transient( $key, $n + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Pull a human error message out of a Chatbase error body.
	 *
	 * @param string $body Raw body.
	 * @return string
	 */
	private function extract_error( $body ) {
		$data = json_decode( $body, true );
		if ( isset( $data['message'] ) ) {
			return sanitize_text_field( $data['message'] );
		}
		if ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
			return sanitize_text_field( $data['error'] );
		}
		return __( 'The assistant is unavailable right now.', 'amplifi-chatbase' );
	}
}
