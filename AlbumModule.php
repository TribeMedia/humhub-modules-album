<?php
/**
 * Displays Albums in a Gridview.
 *
 * @author rifaideen
 */
class AlbumModule extends HWebModule
{
    public $subLayout = "application.modules_core.dashboard.views._layout";
    
    public $defaultController = 'index';

    private $_assetsUrl;

    /**
     * Attach user module behavior to provide this module to user.
     */
    public function behaviors()
    {
    	return array(
    		'UserModuleBehavior' => array(
                'class' => 'application.modules_core.user.behaviors.UserModuleBehavior',
            )
    	);
    }

    /**
     * Get Assets url for this module.
     */
    public function getAssetsUrl()
    {
        if ($this->_assetsUrl === null) {
            
            $this->_assetsUrl = Yii::app()->getAssetManager()->publish(
                Yii::getPathOfAlias('album.assets')
            );
        }
        return $this->_assetsUrl;
    }
    
    /**
     * Add Album Menu to user profile menu.
     */
    public static function onProfileMenuInit($event)
    {

        $user = Yii::app()->getController()->getUser();

        if ($user->isModuleEnabled('album')) {
            $event->sender->addItem(array(
                'label' => 'Album',
                'url' => Yii::app()->createUrl('//album', array('username' => $user->username,'uguid' => $user->guid)),
                'isActive' => Yii::app()->controller->module && Yii::app()->controller->module->id == 'album',
            ));
        }
    }

    /**
     * Create new folder to save albums on enabled.
     */
    public function enable()
    {
        parent::enable();
        
        $path = Yii::getPathOfAlias('webroot') .
                DIRECTORY_SEPARATOR . "uploads" .
                DIRECTORY_SEPARATOR . 'album';
        if (!is_dir($path)) {
            mkdir($path,0777,true);
        }
        
        $blacklisted_objects = explode(',', HSetting::Get('showFilesWidgetBlacklist','file'));
        if (!in_array('Album', $blacklisted_objects)) {
            $blacklisted_objects[] = 'Album';
            HSetting::Set('showFilesWidgetBlacklist', implode(',', $blacklisted_objects));
        }
    }
    
    /**
     * Delete All User Created files
     */
    public function disable()
    {
        if (parent::disable()) {
            foreach (Album::model()->findAll() as $album) {
                $album->delete();
            }
            
            $blacklisted_objects = explode(',', HSetting::Get('showFilesWidgetBlacklist','file'));
            if (false !== ($key = array_search('Album', $blacklisted_objects))) {
                unset($blacklisted_objects[$key]);
                HSetting::Set('showFilesWidgetBlacklist', implode(',', $blacklisted_objects));
            }
        
            return true;
        }

        return false;
    }
    
    /**
     * delete all albums created by the given user.
     * @param User $user
     */
    public function disableUserModule(User $user)
    {
        $albums = Album::model()->findAll('created_by = :user', array(':user'=>$user->id));
        foreach ($albums as $album) {
            $album->delete();
        }
    }
}
