<?php
/*
  Duplicator Pro Plugin
  Copyright (C) 2015, Snap Creek LLC
  website: snapcreek.com

  Duplicator Pro Plugin is distributed under the GNU General Public License, Version 3,
  June 2007. Copyright (C) 2007 Free Software Foundation, Inc., 51 Franklin
  St, Fifth Floor, Boston, MA 02110, USA

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!class_exists('DUP_PRO_Constants'))
{

    /**
     * @copyright 2015 Snap Creek LLC
     */
    class DUP_PRO_Constants
    {
        const PLUGIN_SLUG = 'duplicator-pro';

        const DAYS_TO_RETAIN_DUMP_FILES = 1;
        const ZIPPED_LOG_FILENAME = 'duplicator_pro_log.zip';
        const ZIP_STRING_LIMIT = 1048576;   // Cutoff for using ZipArchive addtostring vs addfile
        const ZIP_MAX_FILE_DESCRIPTORS = 50; // How many file descriptors are allowed to be outstanding (addfile has issues)
      //  const LICENSE_STATUS_TRANSIENT_NAME = 'duplicator_pro_ls';

        const TEMP_CLEANUP_SECONDS = 900;   // 15 min = How many seconds to keep temp files around when delete is requested 
        
        const MAX_LOG_SIZE = 200000;    // The higher this is the more overhead
        
        const LICENSE_KEY_OPTION_NAME = 'duplicator_pro_license_key';
        
        const MAX_BUILD_RETRIES = 10; // Max number of tries doing the same part of the package before auto cancelling

        /* Pseudo constants */
        public static $PACKAGES_SUBMENU_SLUG;
        public static $SCHEDULES_SUBMENU_SLUG;
        public static $STORAGE_SUBMENU_SLUG;
        public static $TEMPLATES_SUBMENU_SLUG;
        public static $TOOLS_SUBMENU_SLUG;
        public static $SETTINGS_SUBMENU_SLUG;
        public static $LOCKING_FILE_FILENAME;
        
        
        public static function init()
        {
            self::$PACKAGES_SUBMENU_SLUG = self::PLUGIN_SLUG;
            self::$SCHEDULES_SUBMENU_SLUG = self::PLUGIN_SLUG . '-schedules';
            self::$STORAGE_SUBMENU_SLUG = self::PLUGIN_SLUG . '-storage';
            self::$TEMPLATES_SUBMENU_SLUG = self::PLUGIN_SLUG . '-templates';
            self::$TOOLS_SUBMENU_SLUG = self::PLUGIN_SLUG . '-tools';
            self::$SETTINGS_SUBMENU_SLUG = self::PLUGIN_SLUG . '-settings';


            self::$LOCKING_FILE_FILENAME = DUPLICATOR_PRO_PLUGIN_PATH . '/dup_pro_lock.bin';
        }

    }

    DUP_PRO_Constants::init();
}
?>
