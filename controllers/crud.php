<?php
/**
 * @package midgardmvc_admin
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Page management controller
 *
 * @package midgardmvc_admin
 */
class midgardmvc_admin_controllers_crud extends midgardmvc_core_controllers_baseclasses_crud
{
    public function __construct(midgardmvc_core_component_interface $instance)
    {
        $this->configuration =& midgardmvc_core::get_instance()->configuration;
        midgardmvc_core::get_instance()->templating->append_directory(midgardmvc_core::get_instance()->componentloader->component_to_filepath('midgardmvc_admin') . '/templates');
    }

    public function load_object(array $args)
    {
        $class = $args['type'];
        if (!class_exists($class))
        {
            throw new midgardmvc_exception_notfound("{$class} is not installed");
        }
        $instance = new $class();
        if (!$instance instanceof midgard_object)
        {
            throw new midgardmvc_exception_notfound("{$class} is not a Midgard type");
        }
        unset($instance);
        $this->object = new $class($args['guid']);
    }
    
    public function prepare_new_object(array $args)
    {
        $class = $args['type'];
        if (!class_exists($class))
        {
            throw new midgardmvc_exception_notfound("{$class} is not installed");
        }
        $instance = new $class();
        if (!$instance instanceof midgard_object)
        {
            throw new midgardmvc_exception_notfound("{$class} is not a Midgard type");
        }
        unset($instance);
        $this->object = new $class();
    }
    
    public function get_url_read()
    {
        return midgardmvc_core::get_instance()->dispatcher->generate_url
        (
            'mvcadmin_crud_read', array
            (
                'type' => get_class($this->object), 
                'guid' => $this->object->guid
            )
        );
    }
    
    public function get_url_update()
    {
        return midgardmvc_core::get_instance()->dispatcher->generate_url
        (
            'mvcadmin_crud_update', array
            (
                'type' => get_class($this->object), 
                'guid' => $this->object->guid
            )
        );
    }
}
?>
