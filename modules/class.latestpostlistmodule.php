<?php
/**
 *  Copyright 2013-2016 Zachary Doll
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @license GPLv2
 * @copyright 2013-2016 Zachary Doll
 */

/**
 * Renders a list of discussions to be displayed in the panel.
 */
class LatestPostListModule extends Gdn_Module {

    protected $latestPosts;
    protected $link = 'discussions';
    
    /**
     * Load data using settings from config.
     * 
     * @param mixed $sender The sending object.
     */
    public function __construct($sender = '') {
        $this->setData(
            c('LatestPostList.Count', 5),
            c('LatestPostList.Link', 'discussions')
        );
        parent::__construct($sender);
    }

    /**
     * Load the discussion data.
     * 
     * @param int $limit How many discussions to load.
     * @param string $link Path for the header link.
     */
    public function setData($limit = 5, $link = 'discussions') {
        $DiscussionModel = new DiscussionModel();
        $this->latestPosts = $DiscussionModel->get(0, $limit, 'all');
        $this->link = $link;
    }

    /**
     * Required for Gdn_IModule.
     * 
     * @return string The asset we want to render in.
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Convenience function used to mark a lists date.
     * 
     * @return int Timestamp of the last comment.
     */
    public function getDate() {
        if ($this->latestPosts->numRows() < 1) {
            return 0;
        }
        $posts = $this->latestPosts->result();
        return $posts[0]->DateLastComment;
    }

    /**
     * Construct a list from the gathered data.
     * 
     * @return string An HTML list.
     */
    public function postList() {
        $posts = '';
        if ($this->latestPosts->numRows() >= 1) {
            foreach ($this->latestPosts->result() as $post) {
                $postTitle = anchor(
                    Gdn_Format::text($post->Name),
                    'discussion/' . $post->DiscussionID . '/' . Gdn_Format::url($post->Name) . '#latest',
                    'PostTitle'
                );

                // If there is a comment, let's use that, otherwise use the original poster
                if ($post->LastName) {
                    $lastPoster = anchor(
                        Gdn_Format::text($post->LastName),
                        'profile/' . $post->LastUserID . '/' . Gdn_Format::url($post->LastName),
                        'PostAuthor'
                    );
                } else {
                    $lastPoster = anchor(
                        Gdn_Format::text($post->FirstName),
                        'profile/' . $post->InsertUserID . '/' . Gdn_Format::url($post->FirstName),
                        'PostAuthor'
                    );
                }

                $postData = wrap(t('on ') . Gdn_Format::date($post->DateLastComment), 'span', array('class' => 'PostDate'));
                $posts .= wrap(
                    $postTitle . wrap(
                        $lastPoster . ' ' . $postData,
                        'div',
                        array('class' => 'Condensed')
                    ),
                    'li',
                    array('class' => ($post->CountUnreadComments > 0) ? 'New' : '')
                );
            }
        }
        return $posts;
    }

    /**
     * Render a string for the target asset.
     * 
     * @return string The complete HTML module.
     */
    public function toString() {
        $string = '';
        if ($this->latestPosts->NumRows() >= 1) {
            $linkString = wrap(t('Latest Posts'), 'h4');
            if ($this->link) {
                $linkString = wrap(anchor(t('Latest Posts'), $this->link), 'h4');
            }
            
            $string .= wrap(
                $linkString . wrap(
                    $this->PostList(),
                    'ul',
                    array('id' => 'LPLUl', 'class' => 'PanelInfo')
                ),
                'div',
                array('class' => 'Box', 'id' => 'LatestPostList')
            );
        }
        return $string;
    }
}
