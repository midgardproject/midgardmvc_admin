<?php
/**
 * @package midgardmvc_admin
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Controller for managing installed Midgard types
 *
 * @package midgardmvc_admin
 */
class midgardmvc_admin_controllers_type
{
    public function __construct(midgardmvc_core_component_interface $instance)
    {
        $this->configuration = $instance->configuration;
    }
    
    /**
     * Show all installed types
     */
    public function get_types(array $args)
    {
        $midcom = midgardmvc_core::get_instance();
        $midcom->templating->append_directory($midcom->componentloader->component_to_filepath('midgardmvc_admin') . '/templates');
        $this->data['types'] = array();
        $types = $midcom->dispatcher->get_mgdschema_classes();
        $largest_type = 1;
        foreach ($types as $type)
        {
            $qb = new midgard_query_builder($type);
            $items = $qb->count();
            $this->data['types'][] = array
            (
                'type'  => $type,
                'label' => $this->get_type_label($type),
                'items' => $items,
                'url'   => $midcom->dispatcher->generate_url('mvcadmin_type_type', array('type' => $type)),
            );
            
            if ($items > $largest_type)
            {
                $largest_type = $items;
            }
        }

        foreach ($this->data['types'] as $id => $type)
        {
            $this->data['types'][$id]['size'] = ceil($type['items'] / $largest_type * 9);
            $this->data['types'][$id]['size']++;
        }
    }

    private function get_type_label($type)
    {
        $type_parts = explode('_', $type);
        return $type_parts[count($type_parts) - 1];
    }

    /**
     * Show a particular type and allow accessing objects in it
     */
    public function get_type(array $args)
    {
        $midcom = midgardmvc_core::get_instance();
        $midcom->templating->append_directory($midcom->componentloader->component_to_filepath('midgardmvc_admin') . '/templates');
        $types = $midcom->dispatcher->get_mgdschema_classes();
        if (!in_array($args['type'], $types))
        {
            throw new midcom_exception_notfound("Type {$args['type']} not available.");
        }

        $this->data['type'] = $args['type'];
        $this->data['type_url'] = $midcom->dispatcher->generate_url('midcom_documentation_class', array('class' => $this->data['type']));

        $reflectionclass = new midgard_reflection_class($this->data['type']);
        $this->data['class_documentation'] = midgardmvc_core_helpers_documentation::get_class_documentation($reflectionclass);

        // We're going to use jQuery DataTables for displaying results of the type
        $midcom->head->enable_jquery();
        $midcom->head->add_jsfile(MIDGARDMVC_STATIC_URL . '/midgardmvc_admin/jquery.dataTables.min.js');
        $midcom->head->add_link_head
        (
            array
            (
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'media' => 'screen',
                'href'  => MIDGARDMVC_STATIC_URL . '/midgardmvc_admin/demo_table_jui.css',
            )
        );
        $midcom->head->add_link_head
        (
            array
            (
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'media' => 'screen',
                'href'  => MIDGARDMVC_STATIC_URL . '/midgardmvc_core/jQuery/ui-darkness/jquery-ui-1.8.custom.css',
            )
        );
        
        $this->data['odata_url'] = $midcom->dispatcher->generate_url('mvcadmin_odata_entries', array('type' => $this->data['type']));
        $this->data['odata_properties'] = $list_properties = midgardmvc_admin_controllers_odata::get_list_properties_for_type($this->data['type']);
    }
}
?>
