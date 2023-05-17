<?php
//  if ( ! function_exists( 'WP_Filesystem' ) ) {
//     require_once ABSPATH . 'wp-admin/includes/file.php';
// }

class checkPlugins {
   
    private int $activePlugins = 0;
    private string $message = '';
    private int $installedPlugins = 0;
    private array $pluginsArr = [];
    private int $amountPlugins = 0;
    private array $neededPlugins = [];
    private int $amountActivePlugins = 0;
    

    public function __construct()
    {
        $this->pluginsArr = $this->getPluginsArray();
        $this->neededPlugins = $this->pluginsArr["sp-plugins"]; 
        $this->amountPlugins = count($this->neededPlugins);
    }

    /**
     * Gives the plugin list as an associative array
     * @return array
     */
    public function getPluginsArray()
    {
        $jsonFilePath = __DIR__.'/allPlugins.json';
        return json_decode(file_get_contents($jsonFilePath), true);
    }

    /**
     * Set the message which will be displayed in the WordPress Backend 
     * @param string $type          Set the of message (error, warning, info, success)
     * @param string $customMessage Set a custom message
     * @return void 
     */
    public function wpSetMessage($type, $customMessage = '')
    {
       $this->message = '<div class="notice notice-'.$type.' is-dismissible">';
    //    $this->message .= match($type) {
    //     'info' => '<p></p>',
    //     'warning' => '<p></p>',
    //     'error' => '<p>Nicht alle Plugins sind installiert</p>',
    //     'success' => '<p>Alle notwendingen Plugins sind installiert.</p>',
    //    };

        if($customMessage !='') {
            $this->message .= '<p>'.$customMessage.'</p>';
        } else {
            switch ($type) {
                case 'info':
                $this->message .= '<p></p>';
                break;
                case 'warning':
                $this->message .= '<p></p>';
                break;
                case 'error':
                $this->message .= '<p>Keiner der vorgegebenen Plugins sind installiert</p>';
                break;
                case 'success':
                $this->message .= '<p>Alle notwendingen Plugins sind installiert.</p>';
                break;
            }
        }
       $this->message .='</div>';
    }

    /**
     * Displayes the message which was set with wpSetMessage
     * @return void
     */
    public function wpSendMessage()
    {
        if ( current_user_can( 'administrator' ) ) {
            echo $this->message;
        }
    }

    // public function isPluginActive($pluginSlug) {
    //     $activePlugins = get_option('active_plugins');
    //     foreach ($activePlugins as $activePlugin) {
    //         if (strpos($activePlugin, $pluginSlug) !== false) {
    //             return true;
    //         }
    //     }
    //     return false;
    // }

    /**
     * Check if the plugin is installed
     * @param string $pluginSlug
     * @return bool
     */
    public function isPluginInstalled($pluginSlug) {
        $pluginPath = WP_PLUGIN_DIR . '/' . $pluginSlug;
        return file_exists($pluginPath);
    }


    /**
     * Check the Plugininstalation and displayes how many plugins are not installed
     * @return void
     */
    public function checkPluginInstallation()
    {
        foreach($this->neededPlugins as $neededPlugin) {
            // $isActive = $this->isPluginActive($neededPlugin['name']);
            $isInstalled = $this->isPluginInstalled($neededPlugin['name']);
            $notInstalled = 0;
            $notActive = 0;

            if($isInstalled) {
                $this->installedPlugins++;

                if($this->isPluginActive) {
                    $this->activePlugins++;
                    $this->amountActivePlugins = ($this->amountPlugins - $this->installedPlugins) - $this->activePlugins;
                } 
            }
            
            $notInstalled = $this->amountPlugins - $this->installedPlugins;
            $notActive =  $this->amountPlugins - $this->amountActivePlugins;

            if($notInstalled != 0 && $notInstalled != $this->amountPlugins ) {
                $this->wpSetMessage('error', $notInstalled.'/'.$this->amountPlugins.' Plugins sind nicht installiert.');
                add_action('admin_notices', [$this, 'wpSendMessage']);
            }else if($notInstalled ==  $this->amountPlugins){
                $this->wpSetMessage('error');
                add_action('admin_notices', [$this, 'wpSendMessage']);
            }else{
                $this->wpSetMessage('success');
                add_action('admin_notices', [$this, 'wpSendMessage']);
            } 
        }
    }
}
