<?php
class bdHeywatch_DevHelper_Config extends DevHelper_Config_Base
{
	protected $_dataClasses = array(
		'log' => array(
			'name' => 'log',
			'camelCase' => 'Log',
			'camelCasePlural' => false,
			'camelCaseWSpace' => 'Log',
			'camelCasePluralWSpace' => false,
			'fields' => array(
				'log_id' => array('name' => 'log_id', 'type' => 'uint', 'autoIncrement' => true),
				'log_date' => array('name' => 'log_date', 'type' => 'uint', 'required' => true),
				'data_id' => array('name' => 'data_id', 'type' => 'uint', 'required' => true),
				'sent' => array('name' => 'sent', 'type' => 'serialized'),
				'received' => array('name' => 'received', 'type' => 'serialized'),
			),
			'phrases' => array(),
			'id_field' => 'log_id',
			'title_field' => false,
			'primaryKey' => array('log_id'),
			'indeces' => array(),
			'files' => array('data_writer' => false, 'model' => false, 'route_prefix_admin' => false, 'controller_admin' => false),
		),
	);
	protected $_dataPatches = array();
	protected $_exportPath = '/Users/sondh/XenForo/_InSocial/bdHeywatch';
	protected $_exportIncludes = array();

	/**
	 * Return false to trigger the upgrade!
	 * common use methods:
	 * 	public function addDataClass($name, $fields = array(), $primaryKey = false, $indeces = array())
	 *	public function addDataPatch($table, array $field)
	 *	public function setExportPath($path)
	**/
	protected function _upgrade()
	{
		return true; // remove this line to trigger update

		/*
		$this->addDataClass(
				'name_here',
				array( // fields
						'field_here' => array(
								'type' => 'type_here',
								// 'length' => 'length_here',
								// 'required' => true,
								// 'allowedValues' => array('value_1', 'value_2'),
								// 'default' => 0,
								// 'autoIncrement' => true,
						),
						// other fields go here
				),
				'primary_key_field_here',
				array( // indeces
						array(
								'fields' => array('field_1', 'field_2'),
								'type' => 'NORMAL', // UNIQUE or FULLTEXT
						),
				),
		);
		*/
	}
}