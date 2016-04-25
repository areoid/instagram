<?php
/*
Library retrieve instagram search by hashtag
File name: Instagram.php
Author: areoid - areg_noid@yahoo.com

Example:
$insta = new Instagram();
$result = $insta->setMaxData(10)
                ->setHashtag('sexy')
                ->retrieve();
OR get like
$likes = new Instagram();
$result = $likes->getLikesByInstagramId('POST_INSTA_ID')
                ->retrieve();

**/
class Instagram
{
    private $_hashtag,
            $_error          = false,
            $_error_message  = '',
            $_cliend_id      = 'YOUR CLIENT ID',
            $_access_token   = 'YOUR ACCESS TOKEN',
            $_max_data       = NULL,
            $_most_liked     = false,
            $_get_likes      = false,
            $_instagram_id   = '',
            $_result         = [],
            $_order_by_liked = NULL;

    /*
    *	Optional method
    *	for initialization sorting data based on liked
    *	@param as string with value that allowed is
    *	asc, ASC, desc, or DESC
    **/
    public function orderByLiked($value = '')
    {
        if(!empty($value))
        {
            if($value == 'asc' || $value == 'ASC' || $value == 'desc' || $value == 'DESC')
            {
                $this->_order_by_liked = $value;
            }
            else
            {
                $this->_error = true;
                $this->_error_message = 'Sorry, @param of orderByLiked() that allowed is asc, ASC, desc, or DESC';
            }
        }
        else
        {
            $this->_error = true;
            $this->_error_message = 'Sorry, @param of orderByLiked() can\'t with empty value';
        }

        return $this;
    }

    /*
    *	Optional method
    *	for initialization mostliked data
    **/
    public function mostLiked()
    {
        $this->_most_liked = true;
        return $this;
    }

    /*
    * Optional method
    * initialization max data that needed
    **/
    public function setMaxData($maxdata)
    {
        if(!empty($maxdata))
        {
            if(is_numeric($maxdata))
            {
                // set @var _max_data
                $this->_max_data = $maxdata;
            }
            else
            {
                $this->_error         = true;
                $this->_error_message = "Sorry, @param setMaxData() only integer";
            }
        }
        else
        {
            $this->_error         = true;
            $this->_error_message = "Sorry, @oaram setMaxData can't with empty value";
        }

        return $this;
    }

    public function setHashtag($hashtag)
    {
        $this->_hashtag = $hashtag;
        return $this;
    }

    /*
    * Get total likes by instagramId
    **/
    public function getLikesByInstagramId($ig_id = "")
    {
        if(!empty($ig_id))
        {
            $this->_instagram_id = $ig_id;
            $this->_get_likes = true;
        }
        else
        {
            $this->_error = true;
            $this->_error_message = "getLikesByInstagramId() Can't with empty value";
        }

        return $this;
    }

    /*
    * Run library start by this methode
    **/
    public function retrieve()
    {
        $this->search();

        if($this->_error)
        {
            $this->_error = false;
            return $this->showErrorMessage();
        }
        else
        {
            return $this->showResults();
        }

    }

    /*
    * engine for search news by hashtag
    **/
    private function search($url = '')
    {
        /*
        * Init host and path request API
        **/
        $host = 'api.instagram.com';
        $path = '/v1/tags/'.$this->_hashtag.'/media/recent?';

        // condition when get likes
        if($this->_get_likes)
        {
            $path = '/v1/media/' . $this->_instagram_id . '?';
        }

        /*
        *	Init options for request API
        **/
        $query = array(
            'client_id'    => $this->_cliend_id,
            'access_token' => $this->_access_token,
        );

        if(empty($url))
        {
            $url = "https://$host$path".http_build_query($query);
        }

        // execute the API and decoded the result
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 3);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response);

        if(empty($response->data))
        {
            $this->_error = true;
            $this->_error_message = $response;

            return $this;
        }
        else
        {
            $temp_response = $response->data;
        }

        if($this->_get_likes)
        {
            $this->_result = $temp_response;
        }
        else
        {
            // merge the result
            $this->_result = array_merge($this->_result, $temp_response);
        }
        unset($temp_response);

        // check is max data is set and the result more or same than max data
        // slicing the result based on the max data
        if(!empty($this->_max_data) AND count($this->_result) >= $this->_max_data)
        {
            $this->_result = array_slice($this->_result, 0, $this->_max_data);
        }
        else
        {
            if(isset($response->pagination->next_url))
            {
                // do recursive
                return $this->search($response->pagination->next_url);
            }
        }

        return true;
    }

    /*
    * For show the results
    * return @array
    **/
    public function showResults()
    {
        $results      = [];
        $data_liked   = [];
        $sort_results = [];

        // need for get total likes by post ig_id (instagramId)
        if($this->_get_likes)
        {
            $returned['ig_id']       = $this->_result->id;
            $returned['total_likes'] = $this->_result->likes->count;

            return $returned;
        }

        foreach ($this->_result as $k => $v)
        {
            $data['ig_id']                             = $v->id;
            $data['created_at']                        = $v->created_time;
            $data['created_at_normal']                 = date('d M Y H:i:s', $v->created_time);
            $data['user_id']                           = $v->user->id;
            $data['user_name']                         = $v->user->username;
            $data['user_profile_picture']              = $v->user->profile_picture;
            $data['user_full_name']                    = $v->user->full_name;
            $data['total_likes']                       = $v->likes->count;
            $data['instagram_url']                     = $v->link;
            $data['type']                              = $v->type;
            $data['text']                              = (!empty($v->caption->text) ? $v->caption->text : "");
            $data['tags']                              = json_encode($v->tags);
            $data['image_low_resolution_320x320']      = $v->images->low_resolution->url;
            $data['image_thumbnail_150x150']           = $v->images->thumbnail->url;
            $data['image_standard_resolution_640x640'] = $v->images->standard_resolution->url;
            if($v->type == "video")
            {
                $data['video_low_resolution_480x480']      = $v->videos->low_resolution->url;
                $data['video_standard_resolution_640x640'] = $v->videos->standard_resolution->url;
                $data['video_low_bandwidth_480x480']       = $v->videos->low_bandwidth->url;
            }

            $data_liked[] = (!empty($v->likes->count) ? $v->likes->count : 0 );

            $results[] = $data;
            unset($data);
        }

        if($this->_most_liked)
        {
            // get index with most liked
            $getIndex = array_search(max($data_liked), $data_liked);
            $returned['most_liked'] = $results[$getIndex];
        }

        if($this->_order_by_liked)
        {
            if($this->_order_by_liked == 'asc' || $this->_order_by_liked == 'ASC')
            {
                asort($data_liked);
                foreach ($data_liked as $key => $value)
                {
                    $sort_results[] = $results[$key];
                }
                unset($results);
                $results = $sort_results;
            }
            else
            {
                arsort($data_liked);
                foreach ($data_liked as $key => $value)
                {
                    $sort_results[] = $results[$key];
                }
                unset($results);
                $results = $sort_results;
            }
        }

        $returned['total'] = count($results);
        $returned['items'] = $results;

        return $returned;
    }

    public function showErrorMessage()
    {
        return $this->_error_message;
    }

}
/*
*	EOF Instagram.php
**/
