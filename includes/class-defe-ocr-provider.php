<?php
/**
 * OCR Providers and Manager for DEFE Custom Tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_OCR_Manager {

    /**
     * Get instance of configured OCR provider
     *
     * @return DEFE_OCR_Interface
     */
    public static function get_provider() {
        $settings = get_option('defe_ocr_settings', array());
        $provider_id = !empty($settings['provider']) ? $settings['provider'] : 'browser_ai';

        switch ($provider_id) {
            case 'gemini':
                return new DEFE_OCR_Gemini_Provider($settings);
            case 'openai':
                return new DEFE_OCR_OpenAI_Provider($settings);
            case 'custom':
                return new DEFE_OCR_Custom_Provider($settings);
            case 'browser_ai':
            default:
                return new DEFE_OCR_Browser_Provider($settings);
        }
    }
}

/**
 * Browser AI / Client Side Provider representation
 */
class DEFE_OCR_Browser_Provider implements DEFE_OCR_Interface {

    protected $settings;

    public function __construct($settings = array()) {
        $this->settings = $settings;
    }

    public function get_id() {
        return 'browser_ai';
    }

    public function get_name() {
        return __('Browser Built-in OCR (Zero Setup)', 'defe-custom-tables');
    }

    public function extract_table($image_path_or_url, $options = array()) {
        // Browser AI runs in the client admin interface via JavaScript OCR / HTML5 canvas parser
        return array(
            'mode' => 'client',
            'message' => __('Processed client-side in the browser.', 'defe-custom-tables')
        );
    }
}

/**
 * Google Gemini Vision Provider for high accuracy table structure extraction
 */
class DEFE_OCR_Gemini_Provider implements DEFE_OCR_Interface {

    protected $settings;

    public function __construct($settings = array()) {
        $this->settings = $settings;
    }

    public function get_id() {
        return 'gemini';
    }

    public function get_name() {
        return __('Google Gemini Vision API', 'defe-custom-tables');
    }

    public function extract_table($image_path_or_url, $options = array()) {
        $api_key = !empty($this->settings['api_key']) ? trim($this->settings['api_key']) : '';
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Google Gemini API Key is missing. Please configure it in DEFE Custom Tables > Settings.', 'defe-custom-tables'));
        }

        $image_data = $this->get_image_base64($image_path_or_url);
        if (is_wp_error($image_data)) {
            return $image_data;
        }

        $model = !empty($this->settings['model']) ? $this->settings['model'] : 'gemini-1.5-flash';
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $system_prompt = "You are a precise table structure and data extraction AI. 
Extract the table visible in this image and return ONLY a valid JSON object matching this schema:
{
  \"name\": \"Suggested Table Title from image if visible, else empty\",
  \"description\": \"Any introductory/description text found above or below the table, else empty\",
  \"header_bg_color\": \"Hex color code of header background if detectable, e.g. #009C3B, else #009C3B\",
  \"columns\": [
    {\"id\": \"col_1\", \"name\": \"Header 1 text\", \"width\": \"auto\", \"align\": \"left\"},
    {\"id\": \"col_2\", \"name\": \"Header 2 text\", \"width\": \"auto\", \"align\": \"left\"}
  ],
  \"rows\": [
    {\"id\": \"row_1\", \"cells\": [\"Cell 1\", \"Cell 2\"]}
  ],
  \"total_row\": {
    \"enabled\": 1,
    \"cells\": [\"Total\", \"2421\"]
  }
}
Rules:
1. Accurately detect all columns and all body rows in proper order.
2. Keep numbers, currencies, dates, and multiline text accurate.
3. If there is a Total or Grand Total row at the bottom, place it in total_row with enabled: 1 and DO NOT duplicate it in rows.
4. Output STRICT JSON only. No markdown fences, no explanatory text.";

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $system_prompt),
                        array(
                            'inline_data' => array(
                                'mime_type' => $image_data['mime_type'],
                                'data' => $image_data['base64'],
                            )
                        )
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.1,
                'response_mime_type' => 'application/json'
            )
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return new WP_Error('api_error', sprintf(__('API Request failed: %s', 'defe-custom-tables'), $response->get_error_message()));
        }

        $code = wp_remote_retrieve_response_code($response);
        $res_body = wp_remote_retrieve_body($response);

        if ($code !== 200) {
            $err_data = json_decode($res_body, true);
            $msg = isset($err_data['error']['message']) ? $err_data['error']['message'] : $res_body;
            return new WP_Error('api_http_error', sprintf(__('API Error (%d): %s', 'defe-custom-tables'), $code, $msg));
        }

        $json = json_decode($res_body, true);
        if (!isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('invalid_response', __('Unexpected API response format.', 'defe-custom-tables'));
        }

        $extracted_text = $json['candidates'][0]['content']['parts'][0]['text'];
        $parsed = json_decode($extracted_text, true);

        if (!is_array($parsed) || empty($parsed['columns'])) {
            // Clean possible markdown wrapper if present
            $cleaned = preg_replace('/^```json|```$/m', '', trim($extracted_text));
            $parsed = json_decode($cleaned, true);
        }

        if (!is_array($parsed) || empty($parsed['columns'])) {
            return new WP_Error('parse_error', __('Could not parse structured table data from OCR response.', 'defe-custom-tables'));
        }

        return $this->normalize_extracted_data($parsed);
    }

    protected function get_image_base64($path_or_url) {
        if (file_exists($path_or_url)) {
            $data = file_get_contents($path_or_url);
            $mime = mime_content_type($path_or_url);
        } else {
            $resp = wp_remote_get($path_or_url, array('timeout' => 30));
            if (is_wp_error($resp)) {
                return $resp;
            }
            $data = wp_remote_retrieve_body($resp);
            $mime = wp_remote_retrieve_header($resp, 'content-type');
        }

        if (empty($data)) {
            return new WP_Error('empty_image', __('Image file is empty or unreadable.', 'defe-custom-tables'));
        }

        if (empty($mime)) {
            $mime = 'image/png';
        }

        return array(
            'mime_type' => $mime,
            'base64' => base64_encode($data),
        );
    }

    protected function normalize_extracted_data($data) {
        $columns = array();
        if (!empty($data['columns']) && is_array($data['columns'])) {
            foreach ($data['columns'] as $i => $col) {
                $name = is_array($col) && isset($col['name']) ? $col['name'] : (is_string($col) ? $col : 'Column ' . ($i + 1));
                $width = is_array($col) && isset($col['width']) ? $col['width'] : 'auto';
                $align = is_array($col) && isset($col['align']) ? $col['align'] : 'left';
                $columns[] = array(
                    'id' => 'col_' . ($i + 1),
                    'name' => sanitize_text_field($name),
                    'width' => sanitize_text_field($width),
                    'align' => in_array($align, array('left', 'center', 'right')) ? $align : 'left',
                    'total_calc' => 'none',
                );
            }
        }

        $rows = array();
        if (!empty($data['rows']) && is_array($data['rows'])) {
            foreach ($data['rows'] as $r_idx => $row) {
                $cells = array();
                $raw_cells = is_array($row) && isset($row['cells']) ? $row['cells'] : (is_array($row) ? $row : array());
                foreach ($raw_cells as $c_val) {
                    $cells[] = sanitize_textarea_field((string)$c_val);
                }
                // Pad or trim to match columns count
                while (count($cells) < count($columns)) {
                    $cells[] = '';
                }
                $rows[] = array(
                    'id' => 'row_' . ($r_idx + 1),
                    'cells' => array_slice($cells, 0, count($columns)),
                );
            }
        }

        $total_row = array(
            'enabled' => !empty($data['total_row']['enabled']) ? 1 : 0,
            'cells' => array(),
        );
        if (!empty($data['total_row']['cells']) && is_array($data['total_row']['cells'])) {
            foreach ($data['total_row']['cells'] as $t_cell) {
                $total_row['cells'][] = sanitize_text_field((string)$t_cell);
            }
            while (count($total_row['cells']) < count($columns)) {
                $total_row['cells'][] = '';
            }
            $total_row['cells'] = array_slice($total_row['cells'], 0, count($columns));
        }

        return array(
            'name' => !empty($data['name']) ? sanitize_text_field($data['name']) : __('Imported Table', 'defe-custom-tables'),
            'description' => !empty($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'header_bg_color' => !empty($data['header_bg_color']) ? sanitize_hex_color($data['header_bg_color']) : '#009C3B',
            'columns' => $columns,
            'rows' => $rows,
            'total_row' => $total_row,
        );
    }
}

/**
 * Custom / OpenAI Compatible Provider
 */
class DEFE_OCR_Custom_Provider extends DEFE_OCR_Gemini_Provider {

    public function get_id() {
        return 'custom';
    }

    public function get_name() {
        return __('Custom OCR Endpoint', 'defe-custom-tables');
    }

    public function extract_table($image_path_or_url, $options = array()) {
        $endpoint = !empty($this->settings['api_endpoint']) ? trim($this->settings['api_endpoint']) : '';
        if (empty($endpoint)) {
            return new WP_Error('no_endpoint', __('Custom OCR Endpoint URL is missing.', 'defe-custom-tables'));
        }

        $image_data = $this->get_image_base64($image_path_or_url);
        if (is_wp_error($image_data)) {
            return $image_data;
        }

        $body = array(
            'image' => $image_data['base64'],
            'mime_type' => $image_data['mime_type'],
            'options' => $options,
        );

        $headers = array('Content-Type' => 'application/json');
        if (!empty($this->settings['api_key'])) {
            $headers['Authorization'] = 'Bearer ' . trim($this->settings['api_key']);
        }

        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $res_body = wp_remote_retrieve_body($response);
        $data = json_decode($res_body, true);

        if (!is_array($data) || empty($data['columns'])) {
            return new WP_Error('invalid_custom_response', __('Invalid response from custom OCR endpoint.', 'defe-custom-tables'));
        }

        return $this->normalize_extracted_data($data);
    }
}

class DEFE_OCR_OpenAI_Provider extends DEFE_OCR_Gemini_Provider {
    public function get_id() {
        return 'openai';
    }

    public function get_name() {
        return __('OpenAI GPT-4o Vision API', 'defe-custom-tables');
    }

    public function extract_table($image_path_or_url, $options = array()) {
        $api_key = !empty($this->settings['api_key']) ? trim($this->settings['api_key']) : '';
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('OpenAI API Key is missing. Please configure it in DEFE Custom Tables > Settings.', 'defe-custom-tables'));
        }

        $image_data = $this->get_image_base64($image_path_or_url);
        if (is_wp_error($image_data)) {
            return $image_data;
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $data_uri = 'data:' . $image_data['mime_type'] . ';base64,' . $image_data['base64'];

        $prompt = "Extract all table structure and data from this image. Output JSON with fields: name, description, header_bg_color, columns (array of {id, name, width, align}), rows (array of {id, cells: []}), and total_row ({enabled: 0|1, cells: []}). Return valid JSON only.";

        $body = array(
            'model' => 'gpt-4o',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array('type' => 'text', 'text' => $prompt),
                        array('type' => 'image_url', 'image_url' => array('url' => $data_uri))
                    )
                )
            ),
            'response_format' => array('type' => 'json_object'),
            'max_tokens' => 4000
        );

        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => wp_json_encode($body),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $res_body = wp_remote_retrieve_body($response);
        $json = json_decode($res_body, true);

        if (isset($json['error'])) {
            return new WP_Error('openai_error', $json['error']['message']);
        }

        if (isset($json['choices'][0]['message']['content'])) {
            $parsed = json_decode($json['choices'][0]['message']['content'], true);
            if (is_array($parsed)) {
                return $this->normalize_extracted_data($parsed);
            }
        }

        return new WP_Error('parse_error', __('Failed to parse table from OpenAI Vision.', 'defe-custom-tables'));
    }
}
