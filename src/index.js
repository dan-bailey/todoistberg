/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';
import TodoListBlock from './blocks/todo-list';
import TodoStatsBlock from './blocks/todo-stats';

/**
 * Register Todoist blocks
 */
registerBlockType('todoistberg/todo-list', {
    title: __('Todoist Task List', 'todoistberg'),
    description: __('Display tasks from a Todoist project.', 'todoistberg'),
    category: 'todoist',
    icon: 'list-view',
    keywords: [
        __('todoist', 'todoistberg'),
        __('tasks', 'todoistberg'),
        __('todo', 'todoistberg'),
        __('list', 'todoistberg')
    ],
    supports: {
        html: false,
        align: ['wide', 'full']
    },
    attributes: {
        projectId: {
            type: 'string',
            default: ''
        },
        maxItems: {
            type: 'number',
            default: 10
        },
        showCompleted: {
            type: 'boolean',
            default: false
        },
        title: {
            type: 'string',
            default: ''
        },
        borderWidth: {
            type: 'number',
            default: 0
        },
        borderColor: {
            type: 'string',
            default: '#ddd'
        },
        borderRadius: {
            type: 'number',
            default: 0
        },
        backgroundColor: {
            type: 'string',
            default: '#fff'
        },
        margin: {
            type: 'number',
            default: 20
        },
        padding: {
            type: 'number',
            default: 20
        },
        headlineAlignment: {
            type: 'string',
            default: 'left'
        }
    },
    edit: TodoListBlock,
    save: () => null // Dynamic block, rendered on server
});


registerBlockType('todoistberg/todo-stats', {
    title: __('Todoist Completion Statistics', 'todoistberg'),
    description: __('Display completed Todoist tasks statistics.', 'todoistberg'),
    category: 'todoist',
    icon: 'chart-bar',
    keywords: [
        __('todoist', 'todoistberg'),
        __('statistics', 'todoistberg'),
        __('stats', 'todoistberg'),
        __('progress', 'todoistberg')
    ],
    supports: {
        html: false,
        align: ['wide', 'full']
    },
    attributes: {
        showToday: {
            type: 'boolean',
            default: true
        },
        showWeek: {
            type: 'boolean',
            default: true
        },
        showMonth: {
            type: 'boolean',
            default: true
        },
        showPastDue: {
            type: 'boolean',
            default: false
        },
        numberColor: {
            type: 'string',
            default: '#007cba'
        },
        title: {
            type: 'string',
            default: 'Todoist Completion Statistics'
        },
        borderWidth: {
            type: 'number',
            default: 0
        },
        borderColor: {
            type: 'string',
            default: '#ddd'
        },
        borderRadius: {
            type: 'number',
            default: 0
        },
        backgroundColor: {
            type: 'string',
            default: '#fff'
        },
        margin: {
            type: 'number',
            default: 20
        },
        padding: {
            type: 'number',
            default: 20
        },
        headlineAlignment: {
            type: 'string',
            default: 'center'
        }
    },
    edit: TodoStatsBlock,
    save: () => null // Dynamic block, rendered on server
});
