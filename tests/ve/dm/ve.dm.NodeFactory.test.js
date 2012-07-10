/**
 * VisualEditor data model NodeFactory tests.
 *
 * @copyright 2011-2012 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

module( 've.dm.NodeFactory' );

/* Stubs */

ve.dm.NodeFactoryNodeStub = function( a, b ) {
	this.a = a;
	this.b = b;
};

ve.dm.NodeFactoryNodeStub.rules = {
	'isContent': true,
	'canContainContent': false,
	'isWrapped': true,
	'childNodeTypes': [],
	'parentNodeTypes': null
};

ve.dm.NodeFactoryNodeStub.converters = null;

ve.dm.NodeFactoryNodeStub.converters = null;

/* Tests */

test( 'getChildNodeTypes', 2, function( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws( function() {
			factory.getChildNodeTypes( 'node-factory-node-stub', 23, { 'bar': 'baz' } );
		},
		/^Unknown node type: node-factory-node-stub$/,
		'throws an exception when getting allowed child nodes of a node of an unregistered type'
	);
	factory.register( 'node-factory-node-stub', ve.dm.NodeFactoryNodeStub );
	assert.deepEqual(
		factory.getChildNodeTypes( 'node-factory-node-stub' ),
		[],
		'gets child type rules for registered nodes'
	);
} );

test( 'getParentNodeTypes', 2, function( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws( function() {
			factory.getParentNodeTypes( 'node-factory-node-stub', 23, { 'bar': 'baz' } );
		},
		/^Unknown node type: node-factory-node-stub$/,
		'throws an exception when getting allowed parent nodes of a node of an unregistered type'
	);
	factory.register( 'node-factory-node-stub', ve.dm.NodeFactoryNodeStub );
	assert.deepEqual(
		factory.getParentNodeTypes( 'node-factory-node-stub' ),
		null,
		'gets parent type rules for registered nodes'
	);
} );

test( 'canNodeHaveChildren', 2, function( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws( function() {
			factory.canNodeHaveChildren( 'node-factory-node-stub', 23, { 'bar': 'baz' } );
		},
		/^Unknown node type: node-factory-node-stub$/,
		'throws an exception when checking if a node of an unregistered type can have children'
	);
	factory.register( 'node-factory-node-stub', ve.dm.NodeFactoryNodeStub );
	assert.strictEqual(
		factory.canNodeHaveChildren( 'node-factory-node-stub' ),
		false,
		'gets child rules for registered nodes'
	);
} );

test( 'canNodeHaveGrandchildren', 2, function( assert ) {
	var factory = new ve.dm.NodeFactory();
	assert.throws( function() {
			factory.canNodeHaveGrandchildren( 'node-factory-node-stub', 23, { 'bar': 'baz' } );
		},
		/^Unknown node type: node-factory-node-stub$/,
		'throws an exception when checking if a node of an unregistered type can have grandchildren'
	);
	factory.register( 'node-factory-node-stub', ve.dm.NodeFactoryNodeStub );
	assert.strictEqual(
		factory.canNodeHaveGrandchildren( 'node-factory-node-stub' ),
		false,
		'gets grandchild rules for registered nodes'
	);
} );

test( 'initialization', 1, function( assert ) {
	assert.ok( ve.dm.nodeFactory instanceof ve.dm.NodeFactory, 'factory is initialized at ve.dm.nodeFactory' );
} );
