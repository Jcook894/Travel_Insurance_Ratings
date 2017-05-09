<?php
defined('_JEXEC') or die('Restricted Access');

class Com_S2frameworkInstallerScript
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
    public function update($parent)
    {
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
        $version = new JVersion();

        if (@!ini_get('safe_mode'))
        {
            set_time_limit(2000);
        }

        $_PATH_COMPONENT = JPATH_ROOT . '/components/com_s2framework';

        $package = JPATH_ADMINISTRATOR . '/components/com_s2framework/s2framework.s2';

        jimport( 'joomla.filesystem.file' );
        jimport( 'joomla.filesystem.folder' );
        jimport( 'joomla.filesystem.archive' );
        jimport( 'joomla.filesystem.path' );

        $adapter = JArchive::getAdapter('zip');

        $result = $adapter->extract($package, $_PATH_COMPONENT);

        if($result)
        {
            JFile::delete($package);
            ?>
            <script type="text/javascript">
            <?php if(version_compare($version->RELEASE, 3, '<')):?>
            window.addEvent('domready', function() {
                var req = new Request({
                  method: 'get',
                  url: '<?php echo JURI::base();?>index.php?option=com_s2framework&format=ajax&install=1',
                }).send();
            });
            <?php else:?>
            jQuery(document).ready(function() {
                jQuery.get('<?php echo JURI::base();?>index.php?option=com_s2framework&format=ajax&install=1');
            });
            <?php endif;?>
            </script>
            <div class="alert alert-success">The S2Framework has been successfully installed.</div>
            <?php
        }
        else {
            ?>
            <div class="alert alert-error">There was a problem installing the S2Framework.</div>
            <?php
        }
    }
}