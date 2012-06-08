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

class Feeds extends MY_Auth_Controller {

    function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');
        $this->load->helper(array('form', 'url'));
    }

    function index()
    {
        $data->page_name = 'Feeds';

        $data->feeds = $this->feed_model->get_active_feeds();

        $this->load->view('admin/_header', $data);
        $this->load->view('admin/feeds', $data);
        $this->load->view('admin/_footer');
    }

    function add()
    {
        $data->page_name = 'Add Feed';

        if ($_POST) {
            if ($this->input->post('url') == 'http://') {
                $_POST['url'] = '';
            }

            $this->form_validation->set_rules('url', 'Url', 'trim|required|xss_clean|callback__test_feed');

            $this->form_validation->set_error_delimiters('<div class="error">', '</div>');

            if ($this->form_validation->run() == FALSE) {
                $this->load->view('admin/_header', $data);
                $this->load->view('admin/feed_add', $data);
            } else {
                $new->feed_title = $this->simplepie->get_title();
                $new->feed_icon = $this->simplepie->get_favicon();
                $new->feed_url = prep_url($this->input->post('url', TRUE));
                $new->feed_status = 'active';

                // Use permalink because sometimes feed is on subdomain which screws up plugin compatibility
                $url = parse_url($this->simplepie->get_permalink());
                if (!$url['host']) {
                    $url = parse_url($this->input->post('url', TRUE));
                }
                if (substr($url['host'], 0, 4) == 'www.') {
                    $new->feed_domain = substr($url['host'], 4);
                } else {
                    $new->feed_domain = $url['host'];
                }
                if (!$new->feed_icon) {
                    $new->feed_icon = 'http://'.$new->feed_domain.'/favicon.ico';
                }
                $this->feed_model->add_feed($new);
                header('Location: '.$this->config->item('base_url').'admin/feeds');
            }
        } else {
            $this->load->view('admin/_header', $data);
            $this->load->view('admin/feed_add', $data);
        }

        $this->load->view('admin/_footer');
    }

    function delete($feed_id)
    {
        $this->feed_model->delete_feed($feed_id);
        header('Location: '.$this->config->item('base_url').'admin/feeds');
    }

    function _test_feed($url)
    {
        $this->simplepie->set_feed_url(prep_url($url));
        $this->simplepie->enable_cache(FALSE);
        $this->simplepie->init();

        // Check if already in the db
        if ($this->db->get_where('feeds', array('feed_url' => $url))->row()) {
            // If it was a deleted feed just reactivate it and forward to feed page
            $feed = $this->db->get_where('feeds', array('feed_url' => $url))->row();
            if ($feed->feed_status == 'deleted') {
                $this->db->update('feeds', array('feed_status' => 'active'), array('feed_id' => $feed->feed_id));
                header('Location: '.$this->config->item('base_url').'admin/feeds');
                exit();
            } else {
                $this->form_validation->set_message('_test_feed', 'You already added that feed...');
                return false;
            }
        } else if ($this->simplepie->error()) {
            $this->form_validation->set_message('_test_feed', $this->simplepie->error());
            return false;
        } else {
            // Looks like the feed is ok
            return true;
        }
    }
}

/* End of file feeds.php */
/* Location: ./application/controllers/admin/feeds.php */
