<?php
/**
 * @package midgardmvc_admin
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * OData provider for Midgard content
 *
 * @package midgardmvc_admin
 */
class midgardmvc_admin_controllers_odata
{
    public function __construct(midgardmvc_core_component_interface $instance)
    {
        $this->configuration = $instance->configuration;
    }

    /**
     * Return a list of object's properties to list in the type listing based on reflection
     *
     * @param string $type MgdSchema type
     */
    public static function get_list_properties_for_type($type, $limit = 5)
    {
        $properties = array();
        $reflector = new midgard_reflection_property($type);

        // TODO: Midgard will provide a new API for getting the properties in right order, use it here
        $dummy = new $type();
        $type_properties = get_object_vars($dummy);

        foreach ($type_properties as $property => $value)
        {
            if (count($properties) >= $limit)
            {
                break;
            }

            if ($property == 'guid')
            {
                continue;
            }

            if ($value instanceof midgard_metadata)
            {
                // Skip metadata for now, later we'll add some of it here
                continue;
            }
            
            if ($reflector->is_link($value))
            {
                // Skip linked fields for now, later we'll use some of here
                continue;
            }
            
            $property_type = $reflector->get_midgard_type($property);
            switch ($property_type)
            {
                case MGD_TYPE_GUID:
                case MGD_TYPE_STRING:
                    $properties[] = $property;
                    break;
            }
        }
        return $properties;
    }

    private function get_type_label($type)
    {
        $type_parts = explode('_', $type);
        return $type_parts[count($type_parts) - 1];
    }

    private function add_constraints(midgard_query_builder &$qb)
    {
        $query = midgardmvc_core::get_instance()->context->query;
        if (isset($query['$top']))
        {
            $qb->set_limit((int) $query['$top']);
        }
        if (isset($query['$skip']))
        {
            $qb->set_offset((int) $query['$skip']);
        } 
    }

    /**
     * Show a particular type and allow accessing objects in it
     */
    public function get_entries(array $args)
    {
        $midcom = midgardmvc_core::get_instance();
        $midcom->templating->append_directory($midcom->componentloader->component_to_filepath('midgardmvc_admin') . '/templates');
        $types = $midcom->dispatcher->get_mgdschema_classes();
        if (!in_array($args['type'], $types))
        {
            throw new midcom_exception_notfound("{$args['type']} is not a Midgard type");
        }

        $type = $args['type'];
        
        $list_properties = midgardmvc_admin_controllers_odata::get_list_properties_for_type($type);
        $objects = array();
        $qb = new midgard_query_builder($type);
        $this->add_constraints($qb);
        $result_objects = $qb->execute();
        foreach ($result_objects as $object)
        {
            foreach ($list_properties as $property)
            {
                if (substr($property, 0, 9) == 'metadata.')
                {
                    $property = substr($property, 9);
                    $object_data[$property] = $object->metadata->$property;
                    continue;
                }
                $object_data[$property] = $object->$property;
            }
            $object_data['guid'] = $object->guid;
            $object_data['url'] =  midgardmvc_core::get_instance()->dispatcher->generate_url
            (
                'mvcadmin_crud_update', array
                (
                    'type' => get_class($object), 
                    'guid' => $object->guid
                )
            );
            $objects[] = $object_data;
        }
        foreach ($list_properties as $id => $property)
        {
            if (substr($property, 0, 9) == 'metadata.')
            {
                $list_properties[$id] = substr($property, 9);
            }
        }
        
        // Prepare OData from this
        $this->data['d'] = array
        (
            'results' => array(),
        );
        
        foreach ($objects as $object)
        {
            $object_array = array();
            // __metadata contains the canonical URL to this query and the type information
            $object_array['__metadata'] = array
            (
                'uri' => '', // TODO: Current URL
                'type' => $type,
            );
            
            $object_array['ID'] = $object['guid'];
            foreach ($list_properties as $id => $property)
            {
                $object_array[$property] = $object[$property];
            }
            $this->data['d']['results'][] = $object_array;
        }
        $this->data['d']['__count'] = count($objects);
    }
}
?>
