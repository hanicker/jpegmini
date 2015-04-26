<?php

class jpegmini
{   private $file_to_upload = 'a_default_picture.jpg';
    private $dir_of_file    = '/home/xok/Pictures';
    private $full_filename_to_upload;
    private $default_json;
    private $final_json;
    private $key;
    private $curl_handler;
    private $curl_post;
    private $curl_options = array(CURLOPT_URL       => '', 
                                  CURLOPT_POST      => false,
                                  CURLOPT_HEADER    => 0,
                                  CURLOPT_VERBOSE   => 0,
                                  CURLOPT_REFERER   => 'http://www.jpegmini.com/main/shrink_photo',
                                  CURLOPT_USERAGENT => 'some/useragent 1.0/here',
                                  CURLOPT_RETURNTRANSFER => true,
                                  );
    private $add_headers  = array(  'X-CSRFToken: 7379b36669b52fa08d2039c7ab2e5e34',
                                    'X-Requested-With: XMLHttpRequest',
                                    'Referer: http://www.jpegmini.com/main/shrink_photo',
                                    'Cookie: csrftoken=7379b36669b52fa08d2039c7ab2e5e34;'
                                 );
    public function __construct($jpeg_file = false)
    {   $this->curl_handler = curl_init();
        if($jpeg_file)
        {  $this->dir_of_file    = dirname($jpeg_file);
           $this->file_to_upload = basename($jpeg_file);
        }
        $this->full_filename_to_upload = $this->dir_of_file .'/'.  $this->file_to_upload;
        $this->default_json = $this->get_policy_json();
        $this->key          = $this->default_json['item_id'];
        $this->final_json   = json_decode($this->upload());
    }
    public function get_mini_filesize()
    { return $this->final_json->{'record'}->{'mini_size'};
    }
    public function get_mini_location()
    { return $this->final_json->{'record'}->{'mini_location'};
    }
    public function get_original_filesize()
    { return $this->final_json->{'record'}->{'src_size'};
    }
    public function get_original_location()
    { return $this->final_json->{'record'}->{'source_location'};
    }
    private function fetch($post = false)
    {   curl_setopt_array($this->curl_handler, $this->curl_options);
        if($post)
        {   curl_setopt($this->curl_handler, CURLOPT_POST, true); 
            curl_setopt($this->curl_handler, CURLOPT_POSTFIELDS, $this->curl_post); 
        }
        curl_setopt($this->curl_handler, CURLOPT_HTTPHEADER, $this->add_headers); 
      return curl_exec($this->curl_handler);
    }
    private function get_policy_json()
    {   $this->curl_options[CURLOPT_URL] = 'http://www.jpegmini.com/website_api/get_upload_policy_params?rand=0.9887820327507041&filename='. $this->file_to_upload;
        $json = json_decode($this->fetch(), true);
      return $json;
    }
    private function start_upload()
    {   $this->curl_options[CURLOPT_URL] = 'http://www.jpegmini.com/website_api/upload_start/'. $this->key;
        $this->curl_post = array();
      return $this->fetch(true); 
    }
    private function upload()
    {   $csrf = $this->start_upload();
        $this->curl_post = array(  'key'                    =>$this->default_json['key'], 
                                   'acl'                    =>$this->default_json['acl'], 
                                   'policy'                 =>$this->default_json['policy'], 
                                   'item_id'                =>$this->default_json['item_id'], 
                                   'Filename'               =>$this->file_to_upload, 
                                   'signature'              =>$this->default_json['signature'], 
                                   'start_url'              =>$this->default_json['start_url'], 
                                   'Content-Type'           =>$this->default_json['Content-Type'], 
                                   'AWSAccessKeyId'         =>$this->default_json['AWSAccessKeyId'], 
                                   'x-amz-meta-filename'    =>$this->default_json['x-amz-meta-filename'], 
                                   'x-amz-meta-username'    =>$this->default_json['x-amz-meta-username'], 
                                   'success_action_redirect'=>$this->default_json['success_action_redirect'], 
                                   'file'                   => '@'. $this->full_filename_to_upload
                               );
        $this->curl_options[CURLOPT_URL]  = 'jpegminiuserdata.s3.amazonaws.com';
        $this->fetch(true);
      return $this->upload_complete();
    }
    private function upload_complete()
    {   $this->curl_options[CURLOPT_URL]   = 'http://www.jpegmini.com/website_api/upload_complete/'. $this->key;
        $this->curl_post = array( 'key'    => 'uploads/anonymous/'. $this->key, 
                                  'bucket' => 'jpegminiuserdata',
                                  );
        $response = $this->fetch(true); 
      return $this->get_status_records();
    }
    private function get_status_records()
    {   $this->curl_options[CURLOPT_URL]  = 'www.jpegmini.com/website_api/status/records/'. $this->key .'?rand=0.05126833551380783';
        $response = $this->fetch();
      return $this->check_conversion_success($response);
    }
    private function check_conversion_success($json_response)
    {   $status = json_decode($json_response);
        if(strtolower($status->{'record'}->{'status'}) == 'success')
          return $json_response;
        else
          return $this->get_status_records();
    }
    function __destruct()
    {     curl_close($this->curl_handler);
    }
}
?>
