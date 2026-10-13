<?php
/**
 * Table builder schema and utility helpers
 */

if (!defined('ABSPATH')) {
    exit;
}

class DEFE_Table_Builder {

    /**
     * Storage reference
     */
    protected $storage;

    /**
     * Constructor
     */
    public function __construct(DEFE_Storage $storage) {
        $this->storage = $storage;
    }

    /**
     * Prepare initial table schema for builder UI
     */
    public function get_builder_schema($table_id = 0) {
        if ($table_id > 0) {
            $table = $this->storage->get_table($table_id);
            if ($table) {
                return $table;
            }
        }

        return DEFE_Storage::get_default_table_data();
    }
}
