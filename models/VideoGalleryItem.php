<?php

namespace wolfguard\video_gallery\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\log\Logger;
use Yii;
use yiidreamteam\upload\VideoUploadBehavior;
use sjaakp\taggable\TaggableBehavior;
use common\models\Tag;

/**
 * VideoGalleryItem ActiveRecord model.
 *
 * Database fields:
 * @property integer $id
 * @property integer $video_gallery_id
 * @property string  $code
 * @property string  $url
 * @property string  $title
 * @property string  $description
 * @property integer $sort
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property VideoGallery $video_gallery
 *
 * @author Ivan Fedyaev <ivan.fedyaev@gmail.com>
 */
class VideoGalleryItem extends ActiveRecord
{
    /** @inheritdoc */
    public function attributeLabels()
    {
        return [
            'video_gallery_id'    => \Yii::t('video_gallery', 'Video gallery'),
            'code'         => \Yii::t('video_gallery', 'Code'),
            'url'          => \Yii::t('video_gallery', 'Video url'),
            'title'        => \Yii::t('video_gallery', 'Title'),
            'description'  => \Yii::t('video_gallery', 'Description'),
            'sort'         => \Yii::t('video_gallery', 'Sort index'),
            'created_at'   => \Yii::t('video_gallery', 'Created at'),
            'updated_at'   => \Yii::t('video_gallery', 'Updated at'),
        ];
    }

    /** @inheritdoc */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            'taggable' => [
                'class' => TaggableBehavior::className(),
                'tagClass' => Tag::className(),
                'junctionTable' => 'ns_article_tag',
            ]    
        ];
    }

    /** @inheritdoc */
    public function scenarios()
    {
        return [
            'create'   => ['code', 'video_gallery_id', 'url', 'title', 'description', 'sort'],
            'update'   => ['code', 'video_gallery_id', 'url', 'title', 'description', 'sort'],
        ];
    }
    public function beforeSave($insert) {
        $this->code = $this->getVideoCode() ;
       return parent::beforeSave($insert);
    }
    /** @inheritdoc */
    public function rules()
    {
        return [
            // code rules
            //['required', 'on' => ['create', 'update']],
            ['code', 'match', 'pattern' => '/^[0-9a-zA-Z\_\.\-]+$/'],
            ['code', 'string', 'min' => 3, 'max' => 255],
            ['code', 'unique'],
            ['code', 'trim'],
            
            ['key_words', 'integer'],

            ['video_gallery_id', 'required'],
            
            ['url', 'required'],
            ['url', 'url'],
            ['url', 'string', 'max' => 255],
            ['url', 'trim'],
            
            ['sort', 'integer'],
            ['sort', 'trim'],
            
            ['title', 'string', 'max' => 255],
            ['title', 'trim'],
            
            ['description', 'safe'],
        ];
    }
    public static function find() {       
        
        return parent::find()->orderBy('id DESC');
    }
    public function create()
    {
        if ($this->getIsNewRecord() == false) {
            throw new \RuntimeException('Calling "' . __CLASS__ . '::' . __METHOD__ . '" on existing object');
        }

        if ($this->save()) {
            \Yii::getLogger()->log('Video gallery image has been created', Logger::LEVEL_INFO);
            return true;
        }

        \Yii::getLogger()->log('An error occurred while creating video gallery image', Logger::LEVEL_ERROR);

        return false;
    }

    /** @inheritdoc */
    public static function tableName()
    {
        return '{{%video_gallery_item}}';
    }

    public function getVideoGallery()
    {
        return $this->hasOne(VideoGallery::className(), ['id' => 'video_gallery_id']);
    }

    public function getVideoCode(){
        if (strpos($this->url, 'youtu') !== false) {
           $text = preg_replace('~(?#!js YouTubeId Rev:20160125_1800)
                # Match non-linked youtube URL in the wild. (Rev:20130823)
                https?://          # Required scheme. Either http or https.
                (?:[0-9A-Z-]+\.)?  # Optional subdomain.
                (?:                # Group host alternatives.
                  youtu\.be/       # Either youtu.be,
                | youtube          # or youtube.com or
                  (?:-nocookie)?   # youtube-nocookie.com
                  \.com            # followed by
                  \S*?             # Allow anything up to VIDEO_ID,
                  [^\w\s-]         # but char before ID is non-ID char.
                )                  # End host alternatives.
                ([\w-]{11})        # $1: VIDEO_ID is exactly 11 chars.
                (?=[^\w-]|$)       # Assert next char is non-ID or EOS.
                (?!                # Assert URL is not pre-linked.
                  [?=&+%\w.-]*     # Allow URL (query) remainder.
                  (?:              # Group pre-linked alternatives.
                    [\'"][^<>]*>   # Either inside a start tag,
                  | </a>           # or inside <a> element text contents.
                  )                # End recognized pre-linked alts.
                )                  # End negative lookahead assertion.
                [?=&+%\w.-]*       # Consume any URL (query) remainder.
                ~ix', '$1',
                $this->url);
            return $text;
        }
        if(strpos($this->url, 'vimeo') !== false){
           $regex = '~
		# Match Vimeo link and embed code
		(?:<iframe [^>]*src=")?         # If iframe match up to first quote of src
		(?:                             # Group vimeo url
				https?:\/\/             # Either http or https
				(?:[\w]+\.)*            # Optional subdomains
				vimeo\.com              # Match vimeo.com
				(?:[\/\w]*\/videos?)?   # Optional video sub directory this handles groups links also
				\/                      # Slash before Id
				([0-9]+)                # $1: VIDEO_ID is numeric
				[^\s]*                  # Not a space
		)                               # End group
		"?                              # Match end quote if part of src
		(?:[^>]*></iframe>)?            # Match the end of the iframe
		(?:<p>.*</p>)?                  # Match any title information stuff
		~ix';
	
            preg_match( $regex, $this->url, $matches );

            return $matches[1];
        }

        return '';
    }

    /**
     * Gets video thumbnail from outside server
     * @param string $version (max, hq, mq, sd or default)
     * @return string Url to image
     */
    public function getVideoImage($version = 'default'){
        if (strpos($this->url, 'youtu') !== false) {
            switch($version){
                case 'max':
                    return 'https://img.youtube.com/vi/'.$this->getVideoCode().'/maxresdefault.jpg';
                    break;

                case 'hq':
                    return 'https://img.youtube.com/vi/'.$this->getVideoCode().'/hqdefault.jpg';
                    break;

                case 'mq':
                    return 'https://img.youtube.com/vi/'.$this->getVideoCode().'/mqdefault.jpg';
                    break;

                case 'sd':
                    return 'https://img.youtube.com/vi/'.$this->getVideoCode().'/sddefault.jpg';
                    break;

                default:
                    return 'https://img.youtube.com/vi/'.$this->getVideoCode().'/default.jpg';
            }
        }
        if(strpos($this->url, 'vimeo') !== false){
        $hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/".$this->getVideoCode().".php"));
            switch($version){
                case 'max':
                    return $hash[0]['thumbnail_large'];
                    break;

                case 'hq':
                    return $hash[0]['thumbnail_large'];
                    break;

                case 'mq':
                    return $hash[0]['thumbnail_medium'];
                    break;

                case 'sd':
                    return $hash[0]['thumbnail_small'];
                    break;

                default:
                    return $hash[0]['thumbnail_large'];
            }
        }

        return '';
    }
    /**
     * Gets video thumbnail from outside server
     * @param string $version (max, hq, mq, sd or default)
     * @return string Url to image
     */
    public function getVideoEmed($w= 640 , $h =400){
        if (strpos($this->url, 'youtu') !== false) {
            return '<div style=" width:'.$w.'px; height:'.$h.'px; " class="pretty-embed" data-pe-videoid="'.  $this->getVideoCode().'" data-pe-fitvids="true"></div>';
            //return '<iframe width="'.  $w.'" height="'.  $h.'" src="//www.youtube.com/embed/'.  $this->getVideoCode().'" frameborder="0" allowfullscreen></iframe>';
        }
        if(strpos($this->url, 'vimeo') !== false){
           return '<iframe src="//player.vimeo.com/video/'.  $this->getVideoCode().'" width="'.  $w.'" height="'.  $h.'" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>'; 
        }       
     return '';   
    } 
    
   
}
