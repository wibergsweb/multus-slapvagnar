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

require_once(dirname(__FILE__) . '/../utilities/class.u.php');
require_once(dirname(__FILE__) . '/../class.verifiers.php');

if (!class_exists('DUP_PRO_JSON_Entity_Base'))
{
    /**
     * Base class for entities that store their data in JSON format
     *
     * @copyright 2015 Snap Creek LLC
     */
    class DUP_PRO_JSON_Entity_Base
    {
        public $id;
        public $type;
        private $dirty;
        private $table_name;
        protected $verifiers;

        const DEFAULT_TABLE_NAME = 'duplicator_pro_entities';

        function __construct($table_name = self::DEFAULT_TABLE_NAME)
        {

            global $wpdb;

            $this->id = -1;
            //$this->type = $type;
            $this->type = get_class($this);

            $this->dirty = false;
            $this->verifiers = array();

            $this->table_name = $wpdb->prefix . $table_name;
        }

        public static function init_table($table_name = self::DEFAULT_TABLE_NAME)
        {

            global $wpdb;

            $table_name = $wpdb->prefix . $table_name;

            $index_query = "select count(*) from information_schema.statistics where table_name = '$table_name' and index_name = 'type_idx' and TABLE_SCHEMA = DATABASE()";

            //DUP_PRO_U::debug("index query=$index_query");
            if ($wpdb->get_var($index_query) != 0)
            {
                $sql = "ALTER TABLE " . $table_name . " DROP INDEX type_idx";

                //     DUP_PRO_U::debug("removing index type_idx for $table_name using sql $sql");
                $wpdb->query($sql);
            }
            else
            {
                DUP_PRO_U::log("index type_idx for $table_name doesn't exist");
            }

            $query_string = "CREATE TABLE IF NOT EXISTS " . $table_name . "(";
            $query_string .= "id INT NOT NULL AUTO_INCREMENT,";
            $query_string .= "type varchar(255), ";
            $query_string .= "data TEXT, ";
           // $query_string .= "PRIMARY KEY  (id)) ENGINE = InnoDB;";
			$query_string .= "PRIMARY KEY  (id))";

			
            //$wpdb->query($query_string);
            dbDelta($query_string);

            $query_string = "CREATE INDEX type_idx ON $table_name (type);";

            $wpdb->query($query_string);
        }
        
        public function insert()
        {
            global $wpdb;

            DUP_PRO_U::log("inserting type $this->type");

            $query_string = "INSERT INTO " . $this->table_name;
            $query_string .= " (type, data) VALUES (%s, %s)";

            $data = DUP_PRO_U::get_public_properties($this);

            $serialized_data = json_encode($data);

            if (strlen($serialized_data) < 65536)
            {

                $prepared_query = $wpdb->prepare($query_string, $this->type, $serialized_data);

                $wpdb->query($prepared_query);

                $this->id = $wpdb->insert_id;

                if ($this->id == false)
                {

                    $this->id = -1;

                    DUP_PRO_U::log_error("Error inserting. Query: " . $prepared_query);

                    return false;
                }
            }
            else
            {

                DUP_PRO_U::log_error("Entity trying to be inserted exceeds max size of 65K!");
                return false;
            }

            return true;
        }

        public function update()
        {
            global $wpdb;

            $query_string = "UPDATE " . $this->table_name;
            $query_string .= " SET type = %s, data = %s WHERE id = %d";

            $data = DUP_PRO_U::get_public_properties($this);

            $serialized_data = json_encode($data);

            if (strlen($serialized_data) < 65536)
            {
                $prepared_query = $wpdb->prepare($query_string, $this->type, $serialized_data, $this->id);
                $wpdb->query($prepared_query);
                $this->dirty = false;

                return true;
            }
            else
            {

                DUP_PRO_U::log_error("Entity trying to be updated exceeds max size of 65K!");
                return false;
            }
        }

        public function delete()
        {

            //   self::delete_by_id($this->id, $this->table_name);

            global $wpdb;

            // $table_name = $wpdb->prefix . $table_name;

            $query_string = "DELETE FROM " . $this->table_name;
            $query_string .= " WHERE id = %d";

            $prepared_query = $wpdb->prepare($query_string, $this->id);

            $wpdb->query($prepared_query);

            $this->id = -1;
            $this->dirty = false;
        }

        public static function get_by_id_and_type($id, $type, $table_name = self::DEFAULT_TABLE_NAME)
        {
            global $wpdb;

            $table_name = $wpdb->prefix . $table_name;

            $query_string = "SELECT * FROM " . $table_name;
            $query_string .= " WHERE id = %d";

            $prepped = $wpdb->prepare($query_string, $id);

            $row = $wpdb->get_row($prepped);

            if ($row != NULL)
            {
                $instance = new $type();

                $instance->id = (int) $row->id;

                //   DUP_PRO_U::ddebug("row id type");
                //   DUP_PRO_U::ddebug(gettype($row->id));
                $instance->type = $row->type;
                $instance->table_name = $table_name;

                $data = json_decode($row->data);

                foreach ($data as $property_name => $property_value)
                {
                    $instance->$property_name = $property_value;
                }

                return $instance;
            }
            else
            {
                DUP_PRO_U::log("get_by_id_and_type: row $prepped is null");
                return null;
            }
        }

        public static function delete_by_id_base($id, $table_name = self::DEFAULT_TABLE_NAME)
        {
            global $wpdb;

            $table_name = $wpdb->prefix . $table_name;

            $query_string = "DELETE FROM " . $table_name;
            $query_string .= " WHERE id = %d";

            $prepared_query = $wpdb->prepare($query_string, $id);

            $wpdb->query($prepared_query);
        }

        public static function delete_by_type_and_field($type, $field_name, $field_value, $table_name = self::DEFAULT_TABLE_NAME)
        {
            $instances = self::get_by_type_and_field($type, $field_name, $field_value, $table_name);

            foreach ($instances as $instance)
            {
                $instance->delete();
            }
        }

        public static function get_by_type_and_field($type, $field_name, $field_value, $table_name = self::DEFAULT_TABLE_NAME)
        {
            $filtered_instances = array();

            $instances = self::get_by_type($type, $table_name);

            foreach ($instances as $instance)
            {
                if ($instance->$field_name == $field_value)
                {
                    array_push($filtered_instances, $instance);
                }
            }

            return $filtered_instances;
        }

        public static function get_by_type($type, $table_name = self::DEFAULT_TABLE_NAME, $page = 0)
        {

            global $wpdb;

            $table_name = $wpdb->prefix . $table_name;

            $query_string = "SELECT * FROM " . $table_name;
            $query_string .= " WHERE type = %s";

            if ($page > 0)
            {

                $records_per_page = 50;

                $offset = ($page - 1) * $records_per_page;

                $query_string .= " LIMIT $offset, $records_per_page";
            }

            $prepared = $wpdb->prepare($query_string, $type);

            $rows = $wpdb->get_results($prepared);

            $instances = array();
            foreach ($rows as $row)
            {

                $instance = new $type();
                $instance->id = $row->id;
                $instance->type = $row->type;
                $instance->table_name = $table_name;

                $data = json_decode($row->data);

                foreach ($data as $property_name => $property_value)
                {
                    $instance->$property_name = $property_value;
                }
                array_push($instances, $instance);
            }

            return $instances;
        }

        public function save()
        {

            $saved = false;


            if ($this->id == -1)
            {
                $saved = $this->insert();
            }
            else //screw the dirty part - too problematic if we update member directlyif ($this->dirty) {
            {
                $saved = $this->update();
                $this->dirty = false;
            }

            return $saved;
        }                

        public function set_post_variables($post)
        {

            $error_string = '';

            // First do a verifier scrub and only then let it fall through to set
            foreach ($post as $key => $value)
            {

                if (is_array($value))
                {
                    foreach ($value as $individual_value)
                    {
                        $local_error = $this->verify_posted_variable($key, $individual_value);

                        if ($local_error != '')
                        {
                            $error_string .= $local_error . ".<br/>";
                        }
                    }
                }
                else
                {
                    $local_error = $this->verify_posted_variable($key, $value);

                    if ($local_error != '')
                    {
                        $error_string .= $local_error . ".<br/>";
                    }
                }
            }

            return $error_string;
        }

        private function verify_posted_variable($key, $value)
        {
            $error_string = '';
            $value = stripslashes($value);

            if (array_key_exists($key, $this->verifiers))
            {

                $error_string = $this->verifiers[$key]->verify($value);

                $this->set($key, $value);
            }
            else
            {
                $this->set($key, $value);
            }

            return $error_string;
        }

        public function set($property_name, $property_value)
        {

            if (property_exists($this->type, $property_name))
            {

                $this->$property_name = $property_value;
                $this->dirty = true;
            }
        }

        public function get($property_name)
        {
            if (property_exists($this->type, $property_name))
            {

                return $this->$property_name;
            }
            else
            {

                return null;
            }
        }
    }
}
?>