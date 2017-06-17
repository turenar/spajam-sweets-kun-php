<?php

/*
 * api-spec/**.yamlからjson schemaを生成しgenerated-api-schema/に保存する
 */

define('PROJECT_DIR', __DIR__ . '/..');
define('SPEC_DIR', PROJECT_DIR . '/api-spec');
define('DEST_REL_DIR', '../generated-api-schema');

require_once __DIR__ . '/../vendor/autoload.php';

function convert_spec_to_schema($spec, $is_input)
{
	$schema = [
		'$schema' => 'http://json-schema.org/draft-04/schema#',
		'type' => 'object',
		'additionalProperties' => false];

	$sub_schema = [];
	$sub_required = [];
	if (!$is_input) {
		$sub_schema['meta'] = ['type' => 'object'];
		$sub_required[] = 'meta';
	}
	foreach ($spec as $sub_name => $sub_spec) {
		$sub_schema[$sub_name] = generate_schema($sub_name, $sub_spec, $sub_required);
	}
	$schema['required'] = $sub_required;
	$schema['properties'] = $sub_schema;
	return $schema;
}

function generate_schema($name, $spec, &$required)
{
	if ($spec['required'] ?? true) {
		$required[] = $name;
	}

	if (isset($spec['+fields']) || isset($spec['+db_fields']) || isset($spec['+include'])) {
		$sub_required = [];
		$sub_properties = [];

		if (isset($spec['+include'])) {
			$load_file = search_include_file($spec['+include']);
			$yaml = yaml_parse_file($load_file);

			if (!isset($yaml[$name])) {
				throw new Exception("element '$name' is not found in $load_file");
			}

			$spec = array_merge_recursive($yaml[$name], $spec);
		}

		if (isset($spec['+db_fields'])) {
			foreach ($spec['+db_fields'] as $field_name) {
				$sub_properties[$field_name] = generate_db_column_schema($name, $field_name, $sub_required);
			}
		}
		if (isset($spec['+fields'])) {
			foreach ($spec['+fields'] as $field_name => $field_spec) {
				$sub_properties[$field_name] = generate_schema($field_name, $field_spec, $sub_required);
			}
		}

		$schema['type'] = 'object';
		$schema['properties'] = $sub_properties;
		$schema['required'] = $sub_required;
		$schema['additionalProperties'] = false;
	} else if (isset($spec['type'])) {
		$schema = generate_field_schema($spec['type']);
	} else {
		throw new Exception('parse failed');
	}

	if ($spec['array'] ?? false) {
		$schema = make_array_type($schema);
	}
	if (!($spec['required'] ?? true)) {
		$schema = make_nullable_type($schema);
	}

	return $schema;
}

function search_include_file($include_file)
{
	$include_file = SPEC_DIR . '/_includes/' . $include_file;
	if (file_exists($include_file)) {
		return $include_file;
	} else {
		throw new Exception("$include_file is not found");
	}
}

function generate_db_column_schema($table_name, $field_name, &$sub_required)
{
	$table_map = get_propel_table_map($table_name);
	$field_info = $table_map->getColumn($field_name);

	$schema = [];
	if ($field_info->isNumeric()) {
		$schema['type'] = 'integer';
	} else if ($field_info->isTemporal()) {
		$schema['type'] = 'string';
	} else if ($field_info->isText()) {
		$schema['type'] = 'string';
	} else {
		throw new Exception("unknown column type of $table_name.$field_name: {$field_info->getType()}");
	}
	if ($field_info->isNotNull()) {
		$sub_required[] = $field_name;
	} else {
		$schema = make_nullable_type($schema);
	}
	return $schema;
}

function generate_field_schema($type)
{
	switch ($type) {
		case 'string':
			return ['type' => 'string'];
		case 'int':
		case 'integer':
			return ['type' => 'integer'];
		case 'number':
		case 'float':
			return ['type' => 'number'];
		case 'date':
			return ['type' => 'string', 'format' => 'date'];
		default:
			throw new Exception("Unknown spec type: $type");
	}
}

function make_array_type($schema)
{
	return [
		'type' => 'array',
		'items' => $schema];
}

function make_nullable_type($schema)
{
	return [
		'anyOf' => [
			$schema,
			['type' => 'null']]];
}

/**
 * @param string $table_name
 * @return \Propel\Runtime\Map\TableMap
 */
function get_propel_table_map($table_name)
{
	$dbMap = \Propel\Runtime\Propel::getServiceContainer()->getDatabaseMap(\ORM\Map\UserTableMap::DATABASE_NAME);
	return $dbMap->getTable($table_name);
}

function init_propel_map()
{
	$reader = new \Propel\Generator\Builder\Util\SchemaReader(new \Propel\Generator\Platform\MysqlPlatform());
	foreach ($reader->parseFile(__DIR__ . "/../db/schema.xml")->getDatabases() as $database) {
		foreach ($database->getTables() as $table) {
			$tableMapClass = "\\ORM\\Map\\{$table->getPhpName()}TableMap";
			$tableMapClass::buildTableMap();
		}
	}
}

function convert_file($infile, $outfile)
{
	$yaml = yaml_parse_file($infile);
	if ($yaml === false) {
		throw new Exception("$infile is not readable as yaml");
	}
	if (!(isset($yaml['input']) && isset($yaml['output']))) {
		throw new Exception("$infile: required root object is not found");
	}

	$schema = [
		'input' => convert_spec_to_schema($yaml['input'], true),
		'output' => convert_spec_to_schema($yaml['output'], false)];
	file_put_contents($outfile, json_encode($schema));
}

function main()
{
	chdir(SPEC_DIR);
	init_propel_map();

	$file_pattern = '@\./[^_].+\.yaml$@';
	$dir = new RecursiveDirectoryIterator('.');
	$ite = new RecursiveIteratorIterator($dir);
	$yaml_files_iterator = new RegexIterator($ite, $file_pattern, RegexIterator::GET_MATCH);
	foreach ($yaml_files_iterator as $yaml_files) {
		foreach ($yaml_files as $yaml_file) {
			if ($yaml_file[0] == '_') { // _includes
				continue;
			}
			$outfile = DEST_REL_DIR . '/' . str_replace('.yaml', '.json', $yaml_file);
			$outdir = dirname($outfile);
			if (!is_dir($outdir)) {
				mkdir($outdir, 0755, true);
			}
			convert_file($yaml_file, $outfile);
		}
	}
}

main();
