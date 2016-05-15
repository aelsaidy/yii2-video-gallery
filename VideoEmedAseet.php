<?php

namespace wolfguard\video_gallery;

use kartik\base\AssetBundle;

/**
 * Description of AnimateAsset
 *
 * @author Ahmad Elsaidy <Ahmad.elsaidy@hotmail.com>
 * @since 2.5
 */
class VideoEmedAseet extends AssetBundle
{
      /**
     * @inheritdoc
     */
    public function init()
    {
        $this->setSourcePath(__DIR__ . '/assets');
        $this->setupAssets('js', ['js/videoasset']);
        parent::init();
    }

}
