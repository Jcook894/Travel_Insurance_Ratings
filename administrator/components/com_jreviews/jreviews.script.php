<?php
defined('_JEXEC') or die;

class com_jreviewsInstallerScript
{
    /**
     * Called before any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function preflight($route, $parent) {}

    /**
     * Called after any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function postflight($route, $parent) {}

    /**
     * Called on installation
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function install($parent)
    {
        self::installPackage();
    }

    /**
     * Called on update
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function update($parent) {

        self::installPackage();
    }

    /**
     * Called on uninstallation
     *
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     */
    public function uninstall($parent) {}

    static function installPackage()
    {
        // Extract the jreviews.s2 package
        if (!ini_get('safe_mode')) {
            set_time_limit(2000);
        }

        $_PATH_COMPONENT = JPATH_ROOT . '/components/com_jreviews';

        $package = JPATH_ADMINISTRATOR . '/components/com_jreviews/jreviews.s2';

        jimport( 'joomla.filesystem.file' );
        jimport( 'joomla.filesystem.folder' );
        jimport( 'joomla.filesystem.archive' );
        jimport( 'joomla.filesystem.path' );

        $adapter = JArchive::getAdapter('zip');

        $result = @$adapter->extract($package, $_PATH_COMPONENT);

        if(!$result)
        {
            ?>
            <div class="alert alert-error">There was a problem extracting the JReviews package</div>
            <?php
        }
        else {

            JFile::delete($package);

            ?>
            <script>document.location.href = 'index.php?option=com_jreviews&url=install';</script>
            <div class="alert alert-success">
                <a href="index.php?option=com_jreviews&url=install">Click to finalize the installation</a>
            </div>
            <?php
        }
    }
}