<?php
if ( ! defined( 'ABSPATH' ) ) {
    require_once __DIR__.'/../../../../wp-load.php';
}

if ( ! function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

class checkPlugins {
   
    private array $toInstall = [];
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
        add_action('wp_ajax_installPlugins', [$this,'installPlugins']);
    }


    function test_ajax_endpoint() {
        die('Ajax endpoint is working.');
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
        }else{
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
        $message = '<p><a class="button button-primary" href="./mods/installPlugins.php">Alle Plugins installieren</a></p>';
        foreach($this->neededPlugins as $neededPlugin) {
            $isInstalled = $this->isPluginInstalled($neededPlugin['name']);
            if($isInstalled) {
                $this->installedPlugins++;
            }else{
                $this->toInstall[] = $neededPlugin['name'];
            }
           
            
            $isNotInstalled = $this->amountPlugins - $this->installedPlugins;
            
            $isAllNotInstalled = $isNotInstalled === $this->amountPlugins;

            //Two ternaire operations to get the message type and the message its self
            $messageType = ($isNotInstalled !== 0 && $isNotInstalled !== $this->amountPlugins) ? 'error' : ($isAllNotInstalled ? 'error' : 'success');
            $messageWithButton = $isNotInstalled . '/' . $this->amountPlugins . ' Plugins sind nicht installiert. 
                        <a class="button button-primary" href="'.esc_url(add_query_arg('action', 'installPlugins', admin_url('admin-ajax.php'))).'">Alle Plugins installieren</a>';
            $message = ($messageType === 'error') ? ($messageWithButton) : '';
            
            $this->wpSetMessage($messageType, $message);
            add_action('admin_notices', [$this, 'wpSendMessage']);
        }
       
    }

   public function installPlugins() {
    if (isset($_GET['action']) && $_GET['action'] === 'installPlugins') {
        $pluginSlugs = $this->toInstall;
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        foreach ($pluginSlugs as $pluginSlug) {
            $pluginFile = basename($pluginSlug);
            $pluginSlug = explode('/', $pluginSlug)[0];

            $pluginInfo = plugins_api('plugin_information', ['slug' => $pluginSlug]);

            if (is_wp_error($pluginInfo)) {
                // Fehler beim Abrufen der Plugin-Informationen
                echo 'Fehler beim Abrufen der Plugin-Informationen: ' . htmlspecialchars($pluginInfo->get_error_message());
                continue;
            }

            $tempDir = WP_CONTENT_DIR . '/temp';

            if (!is_dir($tempDir) && !wp_mkdir_p($tempDir)) {
                echo 'Fehler beim Erstellen des temporären Verzeichnisses: ' . htmlspecialchars($tempDir);
                continue;
            }

            $pluginZipUrl = $pluginInfo->download_link;
            $zipFilePath = $tempDir . '/' . basename($pluginZipUrl);

            // Zip-Datei herunterladen
            $response = wp_remote_get($pluginZipUrl, ['timeout' => 120]);

            if (is_wp_error($response)) {
                echo 'Fehler beim Herunterladen der Zip-Datei: ' . htmlspecialchars($response->get_error_message());
                continue;
            }

            // Zip-Datei speichern
            if (!file_put_contents($zipFilePath, $response['body'])) {
                echo 'Fehler beim Speichern der Zip-Datei.';
                continue;
            }

            WP_Filesystem();

            $result = unzip_file($zipFilePath, $tempDir);

            if (is_wp_error($result)) {
                // Fehler beim Entpacken des Plugin-Zip-Archivs
                echo 'Fehler beim Entpacken des Plugin-Zip-Archivs: ' . htmlspecialchars($result->get_error_message());
                continue;
            }

            $destinationDir = WP_PLUGIN_DIR . '/' . $pluginSlug;

            if (!is_dir($destinationDir) && !wp_mkdir_p($destinationDir)) {
                echo 'Fehler beim Erstellen des Zielverzeichnisses: ' . htmlspecialchars($destinationDir);
                continue;
            }

            $result = $this->copyPluginFiles($tempDir . '/' . $pluginSlug, $destinationDir);

            if (is_wp_error($result)) {
                // Fehler beim Kopieren des Plugins in das Zielverzeichnis
                echo 'Fehler beim Kopieren des Plugins in das Zielverzeichnis: ' . htmlspecialchars($result->get_error_message());
                continue;
            }

            // Plugin erfolgreich installiert
            echo $destinationDir . '/' . $pluginFile;
            
            // Plugin aktivieren
            $pluginPath = $destinationDir . '/' . $pluginFile;
            $activationResult = activate_plugin($pluginPath);

            if (is_wp_error($activationResult)) {
                // Fehler beim Aktivieren des Plugins
                echo 'Fehler beim Aktivieren des Plugins: ' . htmlspecialchars($activationResult->get_error_message());
                continue;
            }

            echo 'Plugin erfolgreich installiert und aktiviert: ' . htmlspecialchars($pluginSlug);
        }

        WP_Filesystem(true);
        $this->removeTempDirectory($tempDir);
    }
}

private function copyPluginFiles($sourceDir, $destinationDir) {
    if (!is_dir($sourceDir)) {
        return new WP_Error('source_dir_not_found', 'Quellverzeichnis nicht gefunden: ' . htmlspecialchars($sourceDir));
    }

    $files = scandir($sourceDir);

    if ($files === false) {
        return new WP_Error('scandir_error', 'Fehler beim Scannen des Quellverzeichnisses: ' . htmlspecialchars($sourceDir));
    }

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $sourcePath = $sourceDir . '/' . $file;
        $destinationPath = $destinationDir . '/' . $file;

        if (is_dir($sourcePath)) {
            if (!is_dir($destinationPath) && !wp_mkdir_p($destinationPath)) {
                return new WP_Error('destination_dir_error', 'Fehler beim Erstellen des Zielverzeichnisses: ' . htmlspecialchars($destinationPath));
            }

            $result = $this->copyPluginFiles($sourcePath, $destinationPath);

            if (is_wp_error($result)) {
                return $result;
            }
        } else {
            if (!copy($sourcePath, $destinationPath)) {
                return new WP_Error('copy_file_error', 'Fehler beim Kopieren der Datei: ' . htmlspecialchars($sourcePath));
            }
        }
    }

    return true;
}

    
    private function removeTempDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
    
        $files = array_diff(scandir($dir), array('.', '..'));
    
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
    
            if (is_dir($path)) {
                $this->removeTempDirectory($path);
            } else {
                unlink($path);
            }
        }
    
        return rmdir($dir);
    }
    
    
    // private function removeTempDirectory($dir) {
    //     if (!is_dir($dir)) {
    //         return false;
    //     }
        
    //     $files = array_diff(scandir($dir), array('.', '..'));
    //     foreach ($files as $file) {
    //         $path = $dir . '/' . $file;
    //         if (is_dir($path)) {
    //             $this->removeTempDirectory($path);
    //         } else {
    //             unlink($path);
    //         }
    //     }
        
    //     return rmdir($dir);
    // }


    // public function installPlugins() {
    //     if (isset($_GET['action']) && $_GET['action'] === 'installPlugins') {
    //         $pluginSlugs = $this->toInstall;
    //         require_once ABSPATH . 'wp-admin/includes/file.php';
    //         require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    
    //         foreach ($pluginSlugs as $pluginSlug) {
    //             $pluginFile = basename($pluginSlug);
    //             $pluginSlug = explode('/', $pluginSlug)[0];
    
    //             $pluginInfo = plugins_api('plugin_information', ['slug' => $pluginSlug]);
    
    //             if (is_wp_error($pluginInfo)) {
    //                 // Fehler beim Abrufen der Plugin-Informationen
    //                 echo 'Fehler beim Abrufen der Plugin-Informationen: ' . htmlspecialchars($pluginInfo->get_error_message());
    //                 continue;
    //             }
    
    //             $pluginZipUrl = $pluginInfo->download_link;
    //             $tempDir = WP_CONTENT_DIR . '/temp';
    
    //             if (!is_dir($tempDir) && !wp_mkdir_p($tempDir)) {
    //                 echo 'Fehler beim Erstellen des temporären Verzeichnisses: ' . htmlspecialchars($tempDir);
    //                 continue;
    //             }
    
    //             // Zip-Datei herunterladen
    //             $response = wp_remote_get($pluginZipUrl);
    //             if (is_wp_error($response)) {
    //                 echo 'Fehler beim Herunterladen der Zip-Datei: ' . htmlspecialchars($response->get_error_message());
    //                 continue;
    //             }
    
    //             $zipFilePath = $tempDir . '/' . $pluginSlug . '.zip';
    
    //             // Zip-Datei speichern
    //             if (!file_put_contents($zipFilePath, $response['body'])) {
    //                 echo 'Fehler beim Speichern der Zip-Datei.';
    //                 continue;
    //             }
    
    //             WP_Filesystem();
    
    //             $result = unzip_file($zipFilePath, $tempDir);
    
    //             if (is_wp_error($result)) {
    //                 // Fehler beim Entpacken des Plugin-Zip-Archivs
    //                 echo 'Fehler beim Entpacken des Plugin-Zip-Archivs: ' . htmlspecialchars($result->get_error_message());
    //                 continue;
    //             }
    
    //             $pluginDir = $tempDir . '/' . $pluginSlug;
    //             $destinationDir = WP_PLUGIN_DIR . '/' . $pluginSlug;
    
    //             if (!is_dir($destinationDir) && !wp_mkdir_p($destinationDir)) {
    //                 echo 'Fehler beim Erstellen des Zielverzeichnisses: ' . htmlspecialchars($destinationDir);
    //                 continue;
    //             }
    
    //             $result = copy_dir($pluginDir, $destinationDir);
    
    //             if (is_wp_error($result)) {
    //                 // Fehler beim Kopieren des Plugins in das Zielverzeichnis
    //                 echo 'Fehler beim Kopieren des Plugins in das Zielverzeichnis: ' . htmlspecialchars($result->get_error_message());
    //                 continue;
    //             }
    
    //             // Plugin erfolgreich installiert und aktiviert
    //             echo $destinationDir . '/' . $pluginFile;
    //             activate_plugin($destinationDir . '/' . $pluginFile);
    //             echo 'Plugin erfolgreich installiert und aktiviert: ' . htmlspecialchars($pluginSlug);
    
    //             WP_Filesystem(true);
    
    //             // Lösche das temporäre Verzeichnis
    //             $this->removeTempDirectory($tempDir);
    //         }
    //     }
    // }
    

    // public function installPlugins() {
    //     $success = true;
    //     if (isset($_GET['action']) && $_GET['action'] === 'installPlugins') {
    //         $pluginSlugs = $this->toInstall;
    //         require_once ABSPATH . 'wp-admin/includes/file.php';
    //         require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
 
    //         foreach ($pluginSlugs as $pluginSlug) {
    //             $pluginFile = basename($pluginSlug);
    //             $pluginSlug = explode('/', $pluginSlug)[0];
                
    //             $pluginInfo = plugins_api('plugin_information', ['slug' => $pluginSlug]);
                
                
    //             if (is_wp_error($pluginInfo)) {
    //                 // Fehler beim Abrufen der Plugin-Informationen
    //                 echo 'Fehler beim Abrufen der Plugin-Informationen: ' . htmlspecialchars($pluginInfo->get_error_message());
    //             } else {
    //                 $pluginZipUrl = $pluginInfo->download_link;
    //                 $tempDir = WP_CONTENT_DIR . '/temp';
            
    //                 if (!is_dir($tempDir)) {
    //                     $mkdirResult = wp_mkdir_p($tempDir);
    //                     if (!$mkdirResult) {
    //                         echo 'Fehler beim Erstellen des temporären Verzeichnisses: ' . htmlspecialchars($tempDir);
    //                         continue;
    //                     }else{
    //                         chmod($tempDir, 0755);
    //                     }
    //                 }


    //                 // Zip-Datei herunterladen
    //                 $response = wp_remote_get($pluginZipUrl);
    //                 if (is_wp_error($response)) {
    //                     echo 'Fehler beim Herunterladen der Zip-Datei: ' . htmlspecialchars($response->get_error_message());
    //                     continue;
    //                 }

    //                 $zipFilePath = $tempDir . '/' . $pluginSlug.'.zip';

    //                 // Zip-Datei speichern
    //                 $fileSaved = file_put_contents($zipFilePath, $response['body']);
    //                 if ($fileSaved === false) {
    //                     echo 'Fehler beim Speichern der Zip-Datei.';
    //                     continue;
    //                 }
                    
    //                 WP_Filesystem();
                    
    //                 $result = unzip_file($zipFilePath, $tempDir);

    //                 if (is_wp_error($result)) {
    //                     // Fehler beim Entpacken des Plugin-Zip-Archivs
    //                     echo 'Fehler beim Entpacken des Plugin-Zip-Archivs: ' . htmlspecialchars( $result->get_error_message());
    //                     $success = false;
    //                 } else {
                         
    //                     $pluginDir = $tempDir . '/' . $pluginSlug;
    //                     $destinationDir = WP_PLUGIN_DIR . '/' . $pluginSlug;
    //                     if (!is_dir($destinationDir)) {
    //                         $mkdirResult = wp_mkdir_p($destinationDir);
    //                         if (!$mkdirResult) {
    //                             echo 'Fehler beim Erstellen des Zielverzeichnisses: ' . htmlspecialchars($destinationDir);
    //                             continue;
    //                         }
    //                     }
    //                     $result = copy_dir($pluginDir, $destinationDir);
                        
    //                     if (is_wp_error($result)) {
    //                         // Fehler beim Kopieren des Plugins in das Zielverzeichnis
    //                         echo 'Fehler beim Kopieren des Plugins in das Zielverzeichnis: ' . htmlspecialchars( $result->get_error_message());
    //                     } else {
    //                         // Plugin erfolgreich installiert und aktiviert
    //                         echo $destinationDir . '/' . $pluginFile;
    //                         activate_plugin($destinationDir . '/' . $pluginFile);
    //                         echo 'Plugin erfolgreich installiert und aktiviert: ' .  htmlspecialchars($pluginSlug);
    //                     }
    //                 }
                    
    //                 WP_Filesystem(true);
    //                 if ($success && is_dir($tempDir)) {
    //                     // Lösche das temporäre Verzeichnis
    //                     $removed = $this->removeTempDirectory($tempDir);
    //                     if (!$removed) {
    //                         echo 'Fehler beim Löschen des temporären Verzeichnisses: ' . htmlspecialchars($tempDir);
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }
}
