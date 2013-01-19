<?php

namespace Thelia\Model\map;

use \RelationMap;
use \TableMap;


/**
 * This class defines the structure of the 'attribute' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 * @package    propel.generator.Thelia.Model.map
 */
class AttributeTableMap extends TableMap
{

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'Thelia.Model.map.AttributeTableMap';

    /**
     * Initialize the table attributes, columns and validators
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('attribute');
        $this->setPhpName('Attribute');
        $this->setClassname('Thelia\\Model\\Attribute');
        $this->setPackage('Thelia.Model');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('ID', 'Id', 'INTEGER', true, null, null);
        $this->addColumn('POSITION', 'Position', 'INTEGER', false, null, null);
        $this->addColumn('CREATED_AT', 'CreatedAt', 'TIMESTAMP', true, null, null);
        $this->addColumn('UPDATED_AT', 'UpdatedAt', 'TIMESTAMP', true, null, null);
        // validators
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
        $this->addRelation('AttributeAv', 'Thelia\\Model\\AttributeAv', RelationMap::ONE_TO_MANY, array('id' => 'attribute_id', ), 'CASCADE', null, 'AttributeAvs');
        $this->addRelation('AttributeCategory', 'Thelia\\Model\\AttributeCategory', RelationMap::ONE_TO_MANY, array('id' => 'attribute_id', ), 'CASCADE', null, 'AttributeCategorys');
        $this->addRelation('AttributeCombination', 'Thelia\\Model\\AttributeCombination', RelationMap::ONE_TO_MANY, array('id' => 'attribute_id', ), 'CASCADE', null, 'AttributeCombinations');
        $this->addRelation('AttributeDesc', 'Thelia\\Model\\AttributeDesc', RelationMap::ONE_TO_MANY, array('id' => 'attribute_id', ), 'CASCADE', null, 'AttributeDescs');
    } // buildRelations()

} // AttributeTableMap