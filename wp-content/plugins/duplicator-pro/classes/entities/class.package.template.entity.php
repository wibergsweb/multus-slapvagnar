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

require_once('class.json.entity.base.php');

if (!class_exists('DUP_PRO_Package_Template_Entity'))
{
    /**
     * @copyright 2015 Snap Creek LLC
     */
    class DUP_PRO_Package_Template_Entity extends DUP_PRO_JSON_Entity_Base
    {
        public $name = '';        
        public $notes;
                
        //ARCHIVE
        public $archive_filter_on = 0;      // Enable File Filters
        public $archive_filter_dirs = '';   // Filtered Directories
        public $archive_filter_exts = '';   // Filtered Extensions
        public $archive_filter_files = '';  // Filtered Files		
        
        //INSTALLER
        public $installer_opts_db_host; // MySQL Server Host
        public $installer_opts_db_name; // Database
        public $installer_opts_db_user; // User
        public $installer_opts_ssl_admin;   // Enforce SSL on Admin
        public $installer_opts_ssl_login;   // Enforce SSL for Logins
        public $installer_opts_cache_wp;    // K??
        public $installer_opts_cache_path;  // ??
        public $installer_opts_url_new;     // New URL
            
        //DATABASE
        public $database_filter_on = 0;					// Enable Table Filters
        public $database_filter_tables = '';			// List of filtered tables               
		public $database_old_sql_compatibility = false;	// Older style sql compatibility
        
        public $is_default = false;

        function __construct()
        {
            parent::__construct();
                                
            $this->verifiers['name'] = new DUP_PRO_Required_Verifier('Name must not be blank');     
            $this->name = DUP_PRO_U::__('New Template');
        }
        
        public static function create_default()
        {
            if(self::get_default_template() == null)
            {
                $template = new DUP_PRO_Package_Template_Entity();

                $template->name = DUP_PRO_U::__('Default');
                $template->notes = DUP_PRO_U::__('The default template.');
                $template->is_default = true;
                                
                $template->save();

                DUP_PRO_U::log('Created default template $template->id');
            }
            else
            {
                // Update it
                
                DUP_PRO_U::log('Default template already exists so not creating');    
            }                    
        }
        
        public function set_post_variables($post)
        {           
            $this->set_checkbox_variable($post, '_archive_filter_on', 'archive_filter_on');
            $this->set_checkbox_variable($post, '_installer_opts_ssl_admin', 'installer_opts_ssl_admin');            
            $this->set_checkbox_variable($post, '_installer_opts_ssl_login', 'installer_opts_ssl_login');
            $this->set_checkbox_variable($post, '_installer_opts_cache_wp', 'installer_opts_cache_wp');
            $this->set_checkbox_variable($post, '_installer_opts_cache_path', 'installer_opts_cache_path');
            $this->set_checkbox_variable($post, '_installer_opts_url_new', 'installer_opts_url_new');
            $this->set_checkbox_variable($post, '_database_filter_on', 'database_filter_on');           
            $this->set_checkbox_variable($post, '_database_old_sql_compatibility', 'database_old_sql_compatibility');           
			
            parent::set_post_variables($post);
        }
        
        private function set_checkbox_variable($post, $key, $name)
        {
            if(isset($post[$key]))
            {
                $this->$name = 1;
            }
            else
            {
                $this->$name = 0;
            }
        }  
        
        public function copy_from_source_id($source_template_id)
        {
            $source_template = self::get_by_id($source_template_id);
                        
            $this->archive_filter_dirs = $source_template->archive_filter_dirs;
            $this->archive_filter_exts = $source_template->archive_filter_exts;
            $this->archive_filter_files = $source_template->archive_filter_files;
            $this->archive_filter_on = $source_template->archive_filter_on;			
			
            $this->database_filter_on = $source_template->database_filter_on;
            $this->database_filter_tables = $source_template->database_filter_tables;
			$this->database_old_sql_compatibility = $source_template->database_old_sql_compatibility;
			
            $this->installer_opts_cache_path = $source_template->installer_opts_cache_path;
            $this->installer_opts_cache_wp = $source_template->installer_opts_cache_wp;
            $this->installer_opts_db_host = $source_template->installer_opts_db_host;
            $this->installer_opts_db_name = $source_template->installer_opts_db_name;
            $this->installer_opts_db_user = $source_template->installer_opts_db_user;
            $this->installer_opts_ssl_admin = $source_template->installer_opts_ssl_admin;
            $this->installer_opts_ssl_login = $source_template->installer_opts_ssl_login;
            $this->installer_opts_url_new = $source_template->installer_opts_url_new;            
            $this->name = sprintf(DUP_PRO_U::__('%1$s - Copy'), $source_template->name);
			
            $this->notes = $source_template->notes;
        }
        
        public static function populate_from_post($post)
        {
            $filter_exts = isset($post['filter-exts']) ? $this->parseExtensionFilter($post['filter-exts']) : '';
            $filter_dirs = isset($post['filter-dirs']) ? $this->parseDirectoryFilter($post['filter-dirs']) : '';
            $filter_files = isset($post['filter-files']) ? $this->parseExtensionFilter($post['filter-files']) : '';
            $tablelist = isset($post['dbtables']) ? implode(',', $post['dbtables']) : '';
            
            // Archive
            $this->archive_filter_on = isset($post['filter-on']) ? 1 : 0;
            $this->archive_filter_dirs = esc_html($filter_dirs);
            $this->archive_filter_exts = str_replace(array('.', ' '), "", esc_html($filter_exts));
            $this->archive_filter_files = str_replace(array('.', ' '), "", esc_html($filter_files));			
            
            // Installer
            $this->installer_opts_db_host = esc_html($post['dbhost']);
            $this->installer_opts_db_name = esc_html($post['dbname']);
            $this->installer_opts_db_user = esc_html($post['dbuser']);
            $this->installer_opts_ssl_admin = isset($post['ssl-admin']) ? 1 : 0;
            $this->installer_opts_ssl_login = isset($post['ssl-login']) ? 1 : 0;
            $this->Installer_opts_cache_wp = isset($post['cache-wp']) ? 1 : 0;
            $this->Installer_opts_cache_path = isset($post['cache-path']) ? 1 : 0;
            $this->Installer_opts_url_new = esc_html($post['url-new']);
            
            // Database
            $this->database_filter_on = isset($post['dbfilter-on']) ? 1 : 0;
            $this->database_filter_tables = esc_html($tablelist);
			$this->database_old_sql_compatibility = isset($post['old-sql-compatibility']) ? 1 : 0;
        }
        
        public static function compare_templates($a, $b)
        {
            /* @var $a DUP_PRO_Package_Template_Entity */
            /* @var $b DUP_PRO_Package_Template_Entity */
            
            if($a->is_default)
            {
                return -1;
            }
            else if($b->is_default)
            {
                return 1;
            }
            else
            {
                return strcasecmp($a->name, $b->name);
            }            
        }
        
        public static function get_all()
        {           
            $templates =  self::get_by_type(get_class());
            
            usort($templates, array('DUP_PRO_Package_Template_Entity', 'compare_templates'));
            
            return $templates;
        }    
        
        public static function delete_by_id($template_id)
        {    
            $schedules = DUP_PRO_Schedule_Entity::get_by_template_id($template_id);
            
            foreach($schedules as $schedule)
            {                
                /* @var $schedule DUP_PRO_Schedule_Entity */
                $schedule->template_id = self::get_default_template()->id;
                
                $schedule->save();
            }
            
            parent::delete_by_id_base($template_id);
        }     
        
        public static function get_default_template()
        {
            $templates = self::get_all();
            
            foreach($templates as $template)
            {
                /* @var $template DUP_PRO_Package_Template_Entity */
                if($template->is_default)
                {
                    return $template;
                }
            }

            return null;
        }

        /**
         * 
         * @param type $id
         * @return DUP_PRO_Package_Template_Entity
         */
        public static function get_by_id($id)
        {
            return self::get_by_id_and_type($id, get_class());
        }
    }           
}
?>