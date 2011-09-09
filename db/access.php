<?php
/**
 * Capability definitions for the OU wiki module.
 *
 * For naming conventions, see lib/db/access.php.
 */
$capabilities = array(

    'mod/ouwiki:edit' => array(

        'riskbitmask' => RISK_SPAM,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),

    'mod/ouwiki:view' => array(

        'riskbitmask' => 0,

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),
    
    'mod/ouwiki:overridelock' => array(

        'riskbitmask' => 0,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),
    
    'mod/ouwiki:viewgroupindividuals'=> array(

        'riskbitmask' => 0,

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),
    
    'mod/ouwiki:viewallindividuals'=> array(

        'riskbitmask' => 0,

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),

    'mod/ouwiki:viewcontributions'=> array(

        'riskbitmask' => 0,

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),
    'mod/ouwiki:deletepage'=> array(

        'riskbitmask' => 0,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),
    'mod/ouwiki:lock'=> array(

        'riskbitmask' => 0,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archtypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    ),
    'mod/ouwiki:annotate'=> array(

        'riskbitmask' => 0,

        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        )
    )
);
