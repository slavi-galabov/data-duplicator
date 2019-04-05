<?php

/*
 * 
 * Description: Library for duplicating data from same table or other table whit same structure
 * 
 * @author Slavi Galabov / Rainbowgrp
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Class Data_duplicator
 */

class Data_duplicator extends CI_Model {
    /*
     * @var errors array
     */

    public $errors;

    /*
     * var $messages array
     */
    public $messages;

    /**
     * __construct
     * 
     * @author Slavi Galabov / Rainbowgrp
     */
    public function __construct() {
        parent::__construct();

        /*
         * Init var errors and messages
         */
        $this->errors = array();
        $this->messages = array();
    }

    /**
     * Copy
     *
     * @author Slavi Galabov 
     * @param    array $replace / array('column' => value_to_replace)
     * @param    array $where_raw 
     * @param    string $table 
     * @param    string $db_donor 
     * @param    string $db_recipient 
     * @return   bool
     */
    public function copy($replace = array(), $where_raw = array(), $table = NULL, $db_donor = NULL, $db_recipient = NULL) {

        if (empty($replace) || empty($where_raw) || !$table) {
            $this->errors[] = __('Please provide all required data!');
            return FALSE;
        }

        $recipient_table = $db_recipient . '.' . $table;
        $donor_table = $db_donor . '.' . $table;

        $table = $this->db->escape_str($table);
        $sql = "DESCRIBE " . $table;
        $table_columns = $this->db->query($sql)->result();

        $select_columns = array();
        $where = array();

        foreach ($table_columns as $key => $column) {

            if ($column->Field == 'id') {

                $select_columns[] = 0;
            } elseif ($column->Field == 'created_by' || $column->Field == 'modify_by') {

                $select_columns[] = userdata()->id;
            } elseif ($column->Field == 'created_on' || $column->Field == 'modify_on') {

                $select_columns[] = time();
            } elseif (key_exists($column->Field, $replace)) {

                $select_columns[] = '"' . $replace[$column->Field] . '"';
            } else {

                $select_columns[] = $column->Field;
            }

            if (key_exists($column->Field, $where_raw)) {
                $where[] = $column->Field . '=' . $where_raw[$column->Field];
            }
        }

        $this->db->select(implode(', ', $select_columns));
        $this->db->where(implode(' AND ', $where));
        $select_query = $this->db->get_compiled_select($donor_table);
        $this->db->reset_query();

        $del_where = $replace;
        $this->db->delete($recipient_table, $del_where);

        $sql = 'INSERT INTO ' . $recipient_table . ' ' . $select_query;

        $res = $this->db->query($sql);
        if ($res) {
            $this->messages[] = __('Successful importing!');
            return TRUE;
        } else {
            $this->errors[] = __('Someting went wrong!');
            return FALSE;
        }
    }

    /**
     * Check
     *
     * @author Slavi Galabov 
     * @param    array $recipient_where 
     * @param    array $donor_where 
     * @param    string $table
     * @param    string $db_donor 
     * @param    string $db_recipient 
     * @return   bool / array 
     */
    public function check($recipient_where = array(), $donor_where = array(), $table = NULL, $db_donor = NULL, $db_recipient = NULL) {

        if (empty($recipient_where) || empty($donor_where) || !$table) {
            $this->errors[] = __('Please provide all required data!');
            return FALSE;
        }

        $table = $this->db->escape_str($table);
        $recipient_table = $db_recipient . '.' . $table;
        $donor_table = $db_donor . '.' . $table;

        $table_columns = $this->compare_tables($donor_table, $recipient_table);

        if (!$table_columns) {
            $this->errors[] = __("Tables doesn't has same structure!");
            return FALSE;
        }

        $this->db->select('*');
        $this->db->where($donor_where);
        $donor = $this->db->get($donor_table)->result();

        $this->db->select('*');
        $this->db->where($recipient_where);
        $recipient = $this->db->get($recipient_table)->result();

        if ($donor || $recipient) {

            $data['donor'] = $donor;
            $data['recipient'] = $recipient;

            return $data;
        } else {
            $this->errors[] = __('Someting went wrong!');
            return FALSE;
        }
    }

    /**
     * Compare tables
     *
     * @author Slavi Galabov
     * @param    string $table_donor 
     * @param    string $table_recipient 
     * @return   bool / array FALSE if NOT match, Table description if they match
     */
    function compare_tables($table_donor = NULL, $table_recipient = NULL) {

        if (!$table_donor || !$table_recipient) {
            return FALSE;
        }

        $donor_sql = "DESCRIBE " . $table_donor;
        $recipient_sql = "DESCRIBE " . $table_recipient;

        $donor_description = $this->db->query($donor_sql)->result();

        if ($donor_sql == $recipient_sql) {
            return $donor_description;
        }

        $recipient_description = $this->db->query($recipient_sql)->result();

        foreach ($donor_description as $key => $column) {
            if (!isset($recipient_description[$key]) || $recipient_description[$key] != $column) {
                return FALSE;
            }
        }
        return $donor_description;
    }

}
