<?php

/**
 * @file
 * Contains \Drupal\protected_pages\ProtectedPagesStorage.
 */

namespace Drupal\protected_pages;

use Drupal\Core\Database\Query\Condition;

/**
 * Provides storage class to handling various database operations.
 */
class ProtectedPagesStorage {


    /**
     * Insert data into protected pages table.
     *
     * @param array $page_data
     *   An array containing all values.
     *
     * @return int $pid
     *   The protected page id.
     */
    public static function insertProtectedPage($page_data) {
        $db = \Drupal::database();
        $query = $db->insert('protected_pages')
            ->fields(array('password', 'path'))
            ->values($page_data);
        $pid = $query->execute();
        return $pid;

    }

    /**
     * Updates data into protected pages table.
     *
     * @param array $page_data
     *   An array containing all values.
     *
     * @param int $pid
     *   The protected page id.
     */
    public static function updateProtectedPage($page_data, $pid) {
        $db = \Drupal::database();
        $db->update('protected_pages')
            ->fields($page_data)
            ->condition('pid', $pid)
            ->execute();

    }

    /**
     * Delete protected page from database.
     *
     * @param int $pid
     *   The protected page id.
     */
    public static function deletePage($pid) {
        $db = \Drupal::database();
        $db->delete('protected_pages')
            ->condition('pid', $pid)
            ->execute();

    }

    /**
     * Fetches protected page records from database.
     *
     * @param array $fields
     *   An array containing all fields.
     * @param array $query_conditions
     *   An array containing all conditions.
     * @param bool $get_single_field
     *   Boolean to check if functions needs to return one or multiple fields.
     */
    public static function load($fields = array(), $query_conditions = array(), $get_single_field = FALSE) {
        $db = \Drupal::database();

        $select = $db->select('protected_pages');
        if (count($fields)) {
            $select->fields('protected_pages', $fields);
        } else {
            $select->fields('protected_pages');
        }

        if (count($query_conditions)) {
            if (isset($query_conditions['or']) && count($query_conditions['or'])) {
                $conditions = new Condition('OR');
                foreach ($query_conditions['or'] as $condition) {
                    $conditions->condition($condition['field'], $condition['value'], $condition['operator']);
                }
                $select->condition($conditions);
            }
            if (isset($query_conditions['and']) && count($query_conditions['and'])) {

                foreach ($query_conditions['and'] as $condition) {
                    $select->condition($condition['field'], $condition['value'], $condition['operator']);
                }

            }
            if (isset($query_conditions['general']) && count($query_conditions['general'])) {

                foreach ($query_conditions['general'] as $condition) {
                    $select->condition($condition['field'], $condition['value'], $condition['operator']);
                }

            }
        }

        if ($get_single_field) {
            $select->range(0, 1);
            $result = $select->execute()->fetchField();
        } else {
            $result = $select->execute()->fetchAll();

        }

        return $result;
    }

}
