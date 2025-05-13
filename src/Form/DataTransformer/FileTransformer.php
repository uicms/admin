<?php
namespace Uicms\Admin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\ImageManager;
use Uicms\App\Service\Uploader;

class FileTransformer implements DataTransformerInterface
{
    protected $max_width = 2000;
    protected $max_height = 2000;
    protected $preview_max_width = 1200;
    protected $preview_max_height = 1200;
    protected $preview_prefix = '_';
    protected $upload_folder = 'uploads';
    protected $upload_path = 'uploads';
    protected $video_generate_thumbnail = true;
    
    function __construct($field_config, $ui_config)
    {
        $this->field_config = $field_config;
        $this->ui_config = $ui_config;

        if(isset($ui_config['upload_path'])) $this->upload_path = $ui_config['upload_path'];
        if(isset($ui_config['upload_folder'])) $this->upload_folder = $ui_config['upload_folder'];
        if(isset($ui_config['image_max_width'])) $this->max_width = $ui_config['image_max_width'];
        if(isset($ui_config['image_max_height'])) $this->max_height = $ui_config['image_max_height'];
        if(isset($ui_config['image_preview_max_width'])) $this->preview_max_width = $ui_config['image_preview_max_width'];
        if(isset($ui_config['image_preview_max_height'])) $this->preview_max_height = $ui_config['image_preview_max_height'];
        if(isset($ui_config['image_preview_prefix'])) $this->preview_prefix = $ui_config['image_preview_prefix'];
        if(isset($ui_config['video_generate_thumbnail'])) $this->video_generate_thumbnail = $ui_config['video_generate_thumbnail'];
    }
    
    public function transform($string)
    {
        if (!$string) {
            return null;
        }
        
        # Check if file exists
        $path = $this->upload_path . '/' . $string;
        if (!file_exists($path)) {
            return null;
        }
        
        # Create File object
        $file = new File($path);
        $file->publicPath = '/' . trim($this->upload_folder, '/') . '/' . $string;
        
        # Add thumbnail path to object
        $thumbnail_string = $this->preview_prefix . $string;
        $file->thumbnailPath = file_exists($this->upload_path . '/' . $thumbnail_string) ? '/' . trim($this->upload_folder, '/') . '/' . $thumbnail_string : '';
        
        # Add dimensions
        if($size = getImageSize($file->getRealPath())) {
            $file->width = $size[0];
            $file->height = $size[1];
        }
        
        return $file;
    }
    
    public function reverseTransform($file)
    {
        if ($file === null) return '';
        
        $mime_type = $file->getMimeType();
        $manager = new ImageManager(array('driver' => 'imagick'));
        
        # Upload
        if($file instanceof UploadedFile) {
            $file_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = strtolower($file->getClientOriginalExtension());
        } else if ($file instanceof File) {
            $file_name = pathinfo($file->getFileName(), PATHINFO_FILENAME);
            $extension = strtolower($file->getExtension());
        } else {
            throw new \Exception('file_error');
        }
        $file_source = $file->getPathName();
        
        /* File name */
        $slugger = new AsciiSlugger();
        $file_name = strtolower($slugger->slug($file_name)) . '-' . uniqid();

        /* Copy file */
        $file_dest = $this->upload_path . '/' . $file_name . '.' . $extension;
        copy($file_source, $file_dest);
        chmod($file_dest, 0755);
        
        /* HEIC */
        if($extension == 'heic') {
            $img = $manager->make($file_dest);
            $extension = 'jpg';
            $file_dest = $this->upload_path . '/' . $file_name . '.' . $extension;
            $img->save($file_dest);
        }
        
        /* WEBP */
        if(isset($this->field_config['convert2webp']) && $this->field_config['convert2webp']) {
            $img = $manager->make($file_dest);
            $extension = 'webp';
            $file_dest = $this->upload_path . '/' . $file_name . '.' . $extension;
            $img->save($file_dest);
        }

        /* Limit image width */
        if(strpos($mime_type, 'image') === 0 && strpos($mime_type, 'svg') === false) {
            $img = $manager->make($file_dest);
            if($extension == 'png') {
                $img->encode('png', 100);
            }
            $img->resize($this->max_width, $this->max_height, function($constraint){
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($file_dest);
        }

        /* Keep source */
        $file_source_dest = $this->upload_path . '/_src_' . $file_name . '.' . $extension;
        copy($file_source, $file_source_dest);
        chmod($file_source_dest, 0755);
        
        /* Make image thumbnail */
        if(strpos($mime_type, 'image') === 0 && strpos($mime_type, 'svg') === false) {
            $img = $manager->make($file_dest);
            if($extension == 'png') {
                $img->encode('png', 100);
            }
            $img->resize($this->preview_max_width, $this->preview_max_height, function($constraint){
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($this->upload_path . '/' . $this->preview_prefix . $file_name . '.' . $extension);
        } else if(strpos($mime_type, 'svg')) {
            copy($file_dest, $this->upload_path . '/' . $this->preview_prefix . $file_name . '.' . $extension);
        }
        
        /* Make video thumbnail */
        if($this->video_generate_thumbnail && strpos($mime_type, 'video') === 0) {
            $cmd_duration = "ffmpeg -i \"" . addslashes($file_dest) . "\" 2>&1 | grep Duration | awk '{print $2}' | tr -d ,";
            if($duration = exec($cmd_duration)) {
                # Calculate half time
                preg_match("'([0-9]{2}):([0-9]{2}):([0-9]{2})'", $duration, $preg);
                $seconds = (($preg[1]*3600) + ($preg[2]*60) + $preg[3])/2;
                $half = sprintf('%02d:%02d:%02d', ($seconds/ 3600),($seconds/ 60 % 60), $seconds% 60);

                # Generate thumbnail
                $path_parts = pathinfo($file_dest);
                exec("ffmpeg -i \"". addslashes($file_dest) . "\" -an -ss $half -r 1 -vframes 1 -y -filter:v scale=\"" . $this->preview_max_width . ":-1\" \"" . $this->upload_path . '/' . $this->preview_prefix . $path_parts['filename']. '.jpg' . "\"");
            } else {
                throw new \Exception('Video not readable or in wrong format or FFMpeg is not installed!');
            }
        }
        
        return $file_name . '.' . $extension;
    }
}