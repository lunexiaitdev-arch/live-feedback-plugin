<?php
namespace LunexiaLiveFeedback\Support;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Centralized REST API response formatting
 */
class REST_Response {
    /**
     * Success response
     */
    public static function success( $data = [], $status = 200, $message = 'Success' ) {
        return new \WP_REST_Response(
            [
                'success' => true,
                'message' => $message,
                'data' => $data,
            ],
            $status
        );
    }

    /**
     * Error response
     */
    public static function error( $message, $code = 'error', $status = 400, $data = [] ) {
        return new \WP_Error(
            $code,
            $message,
            array_merge( [ 'status' => $status ], $data )
        );
    }

    /**
     * Paginated response
     */
    public static function paginated( $items, $total, $page = 1, $per_page = 20 ) {
        $pages = ceil( $total / $per_page );

        return [
            'data' => $items,
            'pagination' => [
                'total' => intval( $total ),
                'pages' => $pages,
                'current_page' => intval( $page ),
                'per_page' => intval( $per_page ),
            ],
        ];
    }

    /**
     * Format sanitized feedback item
     */
    public static function feedback_item( $feedback ) {
        return [
            'id' => intval( $feedback->id ),
            'page_url' => esc_url_raw( $feedback->page_url ),
            'selector' => ! empty( $feedback->selector ) ? sanitize_text_field( $feedback->selector ) : null,
            'coord_x' => intval( $feedback->coord_x ),
            'coord_y' => intval( $feedback->coord_y ),
            'viewport_width' => intval( $feedback->viewport_width ),
            'viewport_height' => intval( $feedback->viewport_height ),
            'screen_width' => intval( $feedback->screen_width ),
            'screen_height' => intval( $feedback->screen_height ),
            'browser_info' => ! empty( $feedback->browser_info ) ? sanitize_text_field( $feedback->browser_info ) : null,
            'author_id' => intval( $feedback->author_id ),
            'status' => sanitize_text_field( $feedback->status ),
            'priority' => sanitize_text_field( $feedback->priority ),
            'screenshot_id' => ! empty( $feedback->screenshot_id ) ? intval( $feedback->screenshot_id ) : null,
            'screenshot_url' => ! empty( $feedback->screenshot_id ) ? esc_url_raw( wp_get_attachment_url( $feedback->screenshot_id ) ) : null,
            'created_at' => $feedback->created_at,
            'updated_at' => ! empty( $feedback->updated_at ) ? $feedback->updated_at : null,
        ];
    }

    /**
     * Format sanitized comment item
     */
    public static function comment_item( $comment ) {
        return [
            'id' => intval( $comment->id ),
            'feedback_id' => intval( $comment->feedback_id ),
            'parent_id' => ! empty( $comment->parent_id ) ? intval( $comment->parent_id ) : null,
            'author_id' => intval( $comment->author_id ),
            'author_name' => ! empty( $comment->author_name ) ? sanitize_text_field( $comment->author_name ) : 'Anonymous',
            'content' => wp_kses_post( $comment->content ),
            'created_at' => $comment->created_at,
        ];
    }
}
