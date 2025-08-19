/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { 
    useBlockProps, 
    InspectorControls,
    RichText
} from '@wordpress/block-editor';
import { 
    Placeholder, 
    Spinner,
    Notice,
    Card,
    CardBody,
    CardHeader,
    PanelBody,
    SelectControl,
    RangeControl,
    ToggleControl,
    ColorPicker
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Todo List Block Edit Component
 */
export default function TodoListBlock({ attributes, setAttributes }) {
    const { projectId, maxItems, showCompleted, title, borderWidth, borderColor, borderRadius, backgroundColor, margin, padding, headlineAlignment } = attributes;
    const [tasks, setTasks] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [projects, setProjects] = useState([]);
    
    const blockProps = useBlockProps({
        className: 'todoistberg-todo-list',
        style: {
            border: borderWidth ? `${borderWidth}px solid ${borderColor}` : 'none',
            borderRadius: `${borderRadius}px`,
            backgroundColor,
            marginBottom: `${margin}px`,
            padding: `${padding}px`
        }
    });

    // Fetch projects on component mount
    useEffect(() => {
        if (window.todoistbergData && window.todoistbergData.projects) {
            setProjects(window.todoistbergData.projects);
        }
    }, []);

    // Fetch tasks when projectId changes
    useEffect(() => {
        if (projectId) {
            fetchTasks();
        } else {
            setTasks([]);
        }
    }, [projectId, maxItems, showCompleted]);

    const fetchTasks = async () => {
        setLoading(true);
        setError('');

        try {
            const response = await fetch(`/wp-json/todoistberg/v1/tasks?project_id=${projectId}&max_items=${maxItems}&show_completed=${showCompleted}`, {
                headers: {
                    'X-WP-Nonce': window.todoistbergData?.nonce || ''
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch tasks');
            }

            const data = await response.json();
            setTasks(data);
        } catch (err) {
            setError(err.message);
            setTasks([]);
        } finally {
            setLoading(false);
        }
    };

    const renderTasks = () => {
        if (loading) {
            return (
                <div style={{ textAlign: 'center', padding: '20px' }}>
                    <Spinner />
                    <p>{__('Loading tasks...', 'todoistberg')}</p>
                </div>
            );
        }

        if (error) {
            return (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            );
        }

        if (tasks.length === 0) {
            return (
                <p className="todoistberg-no-tasks">
                    {__('No tasks found.', 'todoistberg')}
                </p>
            );
        }

        return (
            <ul className="todoistberg-tasks">
                {tasks.map((task) => (
                    <li key={task.id} className={`todoistberg-task ${task.completed ? 'completed' : ''}`}>
                        <span className="todoistberg-task-content">
                            {task.content}
                        </span>
                        {task.due && (
                            <span className="todoistberg-task-due">
                                {new Date(task.due.date).toLocaleDateString()}
                            </span>
                        )}
                    </li>
                ))}
            </ul>
        );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Todoist Settings', 'todoistberg')}>
                    <SelectControl
                        label={__('Project', 'todoistberg')}
                        value={projectId}
                        options={[
                            { label: __('Select a project...', 'todoistberg'), value: '' },
                            { label: __('All Projects', 'todoistberg'), value: 'all' },
                            ...projects
                        ]}
                        onChange={(value) => setAttributes({ projectId: value })}
                    />
                    
                    <RangeControl
                        label={__('Maximum Items', 'todoistberg')}
                        value={maxItems}
                        onChange={(value) => setAttributes({ maxItems: value })}
                        min={1}
                        max={50}
                    />
                    
                    <ToggleControl
                        label={__('Show Completed Tasks', 'todoistberg')}
                        checked={showCompleted}
                        onChange={(value) => setAttributes({ showCompleted: value })}
                    />
                </PanelBody>
                
                <PanelBody title={__('Block Styling', 'todoistberg')} initialOpen={false}>
                    <RangeControl
                        label={__('Border Width', 'todoistberg')}
                        value={borderWidth}
                        onChange={(value) => setAttributes({ borderWidth: value })}
                        min={0}
                        max={10}
                    />
                    
                    <div style={{ marginTop: '16px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase' }}>
                            {__('Border Color', 'todoistberg')}
                        </label>
                        <ColorPicker
                            color={borderColor}
                            onChange={(value) => setAttributes({ borderColor: value })}
                            enableAlpha
                        />
                    </div>
                    
                    <RangeControl
                        label={__('Corner Radius', 'todoistberg')}
                        value={borderRadius}
                        onChange={(value) => setAttributes({ borderRadius: value })}
                        min={0}
                        max={50}
                    />
                    
                    <div style={{ marginTop: '16px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontSize: '11px', fontWeight: '500', textTransform: 'uppercase' }}>
                            {__('Background Color', 'todoistberg')}
                        </label>
                        <ColorPicker
                            color={backgroundColor}
                            onChange={(value) => setAttributes({ backgroundColor: value })}
                            enableAlpha
                        />
                    </div>
                    
                    <RangeControl
                        label={__('Margin (px)', 'todoistberg')}
                        value={margin}
                        onChange={(value) => setAttributes({ margin: value })}
                        min={0}
                        max={100}
                    />
                    
                    <RangeControl
                        label={__('Padding (px)', 'todoistberg')}
                        value={padding}
                        onChange={(value) => setAttributes({ padding: value })}
                        min={0}
                        max={100}
                    />
                </PanelBody>
                
                <PanelBody title={__('Headline Settings', 'todoistberg')} initialOpen={false}>
                    <SelectControl
                        label={__('Headline Alignment', 'todoistberg')}
                        value={headlineAlignment}
                        options={[
                            { label: __('Left', 'todoistberg'), value: 'left' },
                            { label: __('Center', 'todoistberg'), value: 'center' },
                            { label: __('Right', 'todoistberg'), value: 'right' }
                        ]}
                        onChange={(value) => setAttributes({ headlineAlignment: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <RichText
                    tagName="h3"
                    className="todoistberg-title"
                    style={{ textAlign: headlineAlignment }}
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                    placeholder={__('Enter title...', 'todoistberg')}
                />
                
                {!window.todoistbergData?.hasToken ? (
                    <Notice status="warning" isDismissible={false}>
                        {__('Please configure your Todoist API token in the plugin settings.', 'todoistberg')}
                    </Notice>
                ) : !projectId ? (
                    <Placeholder
                        icon="list-view"
                        label={__('Todoist Task List', 'todoistberg')}
                        instructions={__('Select a project from the block settings to display tasks.', 'todoistberg')}
                    />
                ) : (
                    <Card>
                        <CardHeader>
                            <h4>{__('Preview', 'todoistberg')}</h4>
                        </CardHeader>
                        <CardBody>
                            {renderTasks()}
                        </CardBody>
                    </Card>
                )}
            </div>
        </>
    );
}
