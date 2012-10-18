<?php if (!defined('BASEPATH')) exit('No direct access allowed.');
/**
 * LifePress - Lifestream software built on the CodeIgniter PHP framework.
 * Copyright (c) 2012, Mitchell McKenna <mitchellmckenna@gmail.com>
 *
 * LifePress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LifePress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LifePress.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This file incorporates work covered by the following copyright and
 * permission notice:
 *
 *     Sweetcron - Self-hosted lifestream software based on the CodeIgniter framework.
 *     Copyright (c) 2008, Yongfook.com & Edible, Inc. All rights reserved.
 *
 *     Sweetcron is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     Sweetcron is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 *     You should have received a copy of the GNU General Public License 
 *     along with Sweetcron.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package     LifePress
 * @author      Mitchell McKenna <mitchellmckenna@gmail.com>
 * @copyright   Copyright (c) 2012, Mitchell McKenna
 * @license     http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

class Feed_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    private function _process(array $data, array $extra_columns = array()) {
        $feed_results = array();
        foreach ($data as $feed_data) {
            $feed = new Feed();
            $feed->hydrate($feed_data, $extra_columns);
            $feed_results[$feed->id] = $feed;
        }

        return $feed_results;
    }

    /**
     * Get all feeds (active and inactive)
     *
     * @param bool $include_count Whether to include feed's item count
     * @return array
     */
    public function get_feeds($include_count = false)
    {
        if ($include_count) {
            $feeds = $this->db->select('feeds.*, count(*) AS item_count')
                ->from('feeds')
                ->join('items', 'items.item_feed_id = feeds.feed_id AND items.item_status != "deleted"', 'left')
                ->group_by('feeds.feed_id')
                ->get()
                ->result();
            $included_columns = array('item_count');
        } else {
            $feeds = $this->db->get('feeds')->result();
            $included_columns = false;
        }

        $this->_process($feeds, $included_columns);
    }

    /**
     * Get count of active feeds
     *
     * @return int
     */
    public function count_active_feeds()
    {
            return $this->db->get_where('feeds', array('feed_status' => 'active'))->num_rows();
    }

    /**
     * Get active feeds
     *
     * @param bool $include_count Whether to include feed's item count
     * @return array
     */
    public function get_active_feeds($include_count = FALSE)
    {
        if ($include_count) {
            $extra_columns = array('item_count');
            $feeds = $this->db->select('feeds.*, count(*) AS item_count')
                ->from('feeds')
                ->join('items', 'items.item_feed_id = feeds.feed_id AND items.item_status != "deleted"', 'left')
                ->where(array('feeds.feed_status' => 'active'))
                ->group_by('feeds.feed_id')
                ->get()
                ->result_array();
        } else {
            $extra_columns = array();
            $feeds = $this->db
                ->get_where('feeds', array('feed_status' => 'active'))
                ->result_array();
        }

        return $this->_process($feeds, $extra_columns);
    }

    /**
     * Get active feed grouped by domain name
     *
     * @return array
     */
    public function get_active_feed_domains()
    {
        return $this->db
            ->select('feed_domain, feed_icon')
            ->group_by('feed_domain')
            ->get_where('feeds', array('feed_status' => 'active'))
            ->result();
    }

    /**
     * Inserts a new Feed record into the database
     *
     * @param Feed $feed
     *
     * @return Feed
     */
    public function add_feed(Feed $feed)
    {
        $this->db->insert('feeds', $feed->toArray());
        $feed->id = $this->db->insert_id();

        return $feed;
    }

    /**
     * Delete a feed
     *
     * @param int $feed_id
     * @return void
     */
    public function delete_feed($feed_id)
    {
        $this->db->update('feeds', array('feed_status' => 'deleted'), array('feed_id' => $feed_id));
    }

    /**
     * Get the number of items for a given feed
     *
     * @param int $feed_id
     * @return int
     */
    public function get_item_count($feed_id) {
        return $this->db
            ->get_where('items', array('item_status !=' => 'deleted', 'item_feed_id' => $feed_id))
            ->num_rows();
    }
}

/* End of file feed_model.php */
/* Location: ./application/models/feed_model.php */
