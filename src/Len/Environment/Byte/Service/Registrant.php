<?php
/**
 * Registrant entity class.
 *
 * @package Len\Environment\Byte\Service
 */

namespace Len\Environment\Byte\Service;

/**
 * Registrant entity class.
 */
class Registrant
{
    /**
     * The ID of the registrant.
     *
     * @var int $_id
     */
    private $_id;

    /**
     * The name of the registrant.
     *
     * @var string $_name
     */
    private $_name;

    /**
     * Registrant constructor.
     *
     * @param int $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->setId($id);
        $this->setName($name);
    }

    /**
     * Setter for the _id property.
     *
     * @param int $id
     * @return Registrant
     * @throws \InvalidArgumentException when $id is not of type int or <= 0.
     */
    private function setId($id)
    {
        if (!is_int($id) || $id <= 0) {
            throw new \InvalidArgumentException(
                'Invalid id supplied: ' . var_export($id, true)
            );
        }

        $this->_id = $id;

        return $this;
    }

    /**
     * Setter for the _name property.
     *
     * @param string $name
     * @return Registrant
     * @throws \InvalidArgumentException when $name is not of type string or
     *   empty.
     */
    private function setName($name)
    {
        if (!is_string($name) || empty($name)) {
            throw new \InvalidArgumentException(
                'Invalid name supplied: ' . var_export($name, true)
            );
        }

        $this->_name = $name;

        return $this;
    }

    /**
     * Getter for the _id property.
     *
     * @return int
     * @throws \LogicException when property _id is not set.
     */
    public function getId()
    {
        if (!isset($this->_id)) {
            throw new \LogicException('Missing property _id');
        }
        return $this->_id;
    }

    /**
     * Getter for the _name property.
     *
     * @return string
     * @throws \LogicException when property _name is not set.
     */
    public function getName()
    {
        if (!isset($this->_name)) {
            throw new \LogicException('Missing property _name');
        }
        return $this->_name;
    }
}
