<?php
namespace Uicms\Admin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;

use Intervention\Image\ImageManagerStatic as Image;
use Uicms\App\Service\Uploader;

class FileTransformer implements DataTransformerInterface
{
    function __construct($field_config, $ui_config) {
        $this->field_config = $field_config;
        $this->ui_config = $ui_config;
    }
    
    public function transform($string)
    {
        if (!$string) {
            return null;
        }
        
        $path = $this->ui_config['upload_path'] . '/' . $string;

        if (!file_exists($path)) {
            return null;
        }
        
        $upload_folder = $this->ui_config['upload_folder'];
        $thumbnail_prefix = $this->ui_config['thumbnail_prefix'];
        
        $file = new File($path);
        $file->publicPath = '/' . trim($upload_folder, '/') . '/' . $string;
        $file->thumbnailPath = '';
        $thumbnail_path = '/' . trim($upload_folder, '/') . '/' . $thumbnail_prefix . $string;
        if(file_exists($thumbnail_path)) {
            $file->thumbnailPath = $thumbnail_path;
        } 

        return $file;
    }

    public function reverseTransform($file)
    {
        if ($file === null) return '';

        $mime_type = $file->getMimeType();
        $uploader = new Uploader($this->ui_config['upload_path']);
        $result = $uploader->upload($file);
        $path = $this->ui_config['upload_path'];
        $path_file = $path . '/' . $result;
        $width_resize = isset($this->field_config['resize_width']) ? $this->field_config['resize_width'] : $this->ui_config['thumbnail_default_width'];
        
        /* Make thumbnail */
        if(strpos($mime_type, 'image') === 0) {
            $path_thumbnail = $path . '/' . $this->ui_config['thumbnail_prefix'] . $result;
            $img = Image::make($path_file);
            $img->resize($width_resize, null, function($constraint){
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($path_thumbnail);
        }
        
        if(strpos($mime_type, 'video') === 0) {
            # Calculate half time
    		$cmd_duration = "ffmpeg -i \"" . addslashes($path_file) . "\" 2>&1 | grep Duration | awk '{print $2}' | tr -d ,";
    		if($duration = exec($cmd_duration)) {
        		preg_match("'([0-9]{2}):([0-9]{2}):([0-9]{2})'", $duration, $preg);
        		$seconds = (($preg[1]*3600) + ($preg[2]*60) + $preg[3])/2;
        		$half = sprintf('%02d:%02d:%02d', ($seconds/ 3600),($seconds/ 60 % 60), $seconds% 60);
            
                # Filter
                $filter = "-filter:v scale=\"" . $width_resize . ":-1\"";
            
                # Generate thumbnail
                $path_parts = pathinfo($path_file);
                $path_thumbnail = $path . '/' . $this->ui_config['thumbnail_prefix'] . $path_parts['filename']. '.jpg';
        		$cmd_thumbnail = "ffmpeg -i \"". addslashes($path_file) . "\" -an -ss $half -r 1 -vframes 1 -y $filter \"" . $path_thumbnail . "\"";
        		exec($cmd_thumbnail);
    		} else {
    		    throw new Exception('Video not readable or in wrong format!');
    		}
        }
        
        return $result;
    }
}