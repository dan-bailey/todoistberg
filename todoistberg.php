<?php
/**
 * Plugin Name: Todoistberg - Todoist Gutenberg Blocks
 * Plugin URI: https://github.com/your-username/todoistberg
 * Description: A collection of Gutenberg blocks for integrating Todoist functionality into WordPress.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: todoistberg
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TODOISTBERG_VERSION', '1.0.1');
define('TODOISTBERG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TODOISTBERG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TODOISTBERG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Todoistberg Plugin Class
 */
class Todoistberg_Plugin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_todoistberg_save_token', array($this, 'save_token_ajax'));
        add_action('wp_ajax_todoistberg_save_settings', array($this, 'save_settings_ajax'));
        add_action('wp_ajax_todoistberg_test_connection', array($this, 'test_connection_ajax'));
        
        // Frontend AJAX handlers
        add_action('wp_ajax_todoistberg_add_task', array($this, 'add_task_ajax'));
        add_action('wp_ajax_nopriv_todoistberg_add_task', array($this, 'add_task_ajax'));
        add_action('wp_ajax_todoistberg_toggle_task', array($this, 'toggle_task_ajax'));
        add_action('wp_ajax_nopriv_todoistberg_toggle_task', array($this, 'toggle_task_ajax'));
        add_action('wp_ajax_todoistberg_get_tasks', array($this, 'get_tasks_ajax'));
        add_action('wp_ajax_nopriv_todoistberg_get_tasks', array($this, 'get_tasks_ajax'));
        add_action('wp_ajax_todoistberg_get_stats', array($this, 'get_stats_ajax'));
        add_action('wp_ajax_nopriv_todoistberg_get_stats', array($this, 'get_stats_ajax'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('todoistberg', false, dirname(TODOISTBERG_PLUGIN_BASENAME) . '/languages');
        
        // Register blocks
        $this->register_blocks();
        
        // Enqueue editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        
        // Enqueue frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }
    
    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Register block script
        wp_register_script(
            'todoistberg-blocks',
            TODOISTBERG_PLUGIN_URL . 'build/index.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            TODOISTBERG_VERSION
        );
        
        // Register block styles
        wp_register_style(
            'todoistberg-blocks-editor',
            TODOISTBERG_PLUGIN_URL . 'build/style-index.css',
            array('wp-edit-blocks'),
            TODOISTBERG_VERSION
        );
        
        wp_register_style(
            'todoistberg-blocks-frontend',
            TODOISTBERG_PLUGIN_URL . 'build/style-index.css',
            array(),
            TODOISTBERG_VERSION
        );
        
        // Register blocks
        register_block_type('todoistberg/todo-list', array(
            'editor_script' => 'todoistberg-blocks',
            'editor_style' => 'todoistberg-blocks-editor',
            'style' => 'todoistberg-blocks-frontend',
            'render_callback' => array($this, 'render_todo_list_block'),
            'attributes' => array(
                'projectId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'maxItems' => array(
                    'type' => 'number',
                    'default' => 10
                ),
                'showCompleted' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'title' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
        
        register_block_type('todoistberg/todo-form', array(
            'editor_script' => 'todoistberg-blocks',
            'editor_style' => 'todoistberg-blocks-editor',
            'style' => 'todoistberg-blocks-frontend',
            'render_callback' => array($this, 'render_todo_form_block'),
            'attributes' => array(
                'projectId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'buttonText' => array(
                    'type' => 'string',
                    'default' => 'Add Task'
                ),
                'placeholder' => array(
                    'type' => 'string',
                    'default' => 'Enter task description...'
                )
            )
        ));
        
        register_block_type('todoistberg/todo-stats', array(
            'editor_script' => 'todoistberg-blocks',
            'editor_style' => 'todoistberg-blocks-editor',
            'style' => 'todoistberg-blocks-frontend',
            'render_callback' => array($this, 'render_todo_stats_block'),
            'attributes' => array(
                'showToday' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showWeek' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showMonth' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'showPastDue' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'numberColor' => array(
                    'type' => 'string',
                    'default' => '#007cba'
                )
            )
        ));
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        wp_enqueue_script('todoistberg-blocks');
        wp_enqueue_style('todoistberg-blocks-editor');
        
        // Localize script with settings
        wp_localize_script('todoistberg-blocks', 'todoistbergData', array(
            'apiUrl' => rest_url('todoistberg/v1/'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('todoistberg_frontend_nonce'),
            'hasToken' => !empty($this->get_todoist_token()),
            'projects' => $this->get_projects_list(),
            'isAdmin' => current_user_can('manage_options')
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style('todoistberg-blocks-frontend');
        
        // Enqueue frontend script for interactive features
        wp_enqueue_script(
            'todoistberg-frontend',
            TODOISTBERG_PLUGIN_URL . 'build/frontend.js',
            array('jquery'),
            TODOISTBERG_VERSION,
            true
        );
        
        wp_localize_script('todoistberg-frontend', 'todoistbergFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('todoistberg_frontend_nonce')
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Todoistberg Settings', 'todoistberg'),
            __('Todoistberg', 'todoistberg'),
            'manage_options',
            'todoistberg-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('todoistberg_options', 'todoistberg_token', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('todoistberg_options', 'todoistberg_timezone', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'UTC'
        ));
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        $token = $this->get_todoist_token();
        $timezone = $this->get_timezone();
        $connection_status = $this->test_connection();
        ?>
        <div class="wrap">
            <h1><?php _e('Todoistberg Settings', 'todoistberg'); ?></h1>
            
            <div class="todoistberg-admin-container">
                <div class="todoistberg-card">
                    <h2><?php _e('Todoist API Configuration', 'todoistberg'); ?></h2>
                    
                    <p><?php _e('To use Todoistberg blocks, you need to provide your Todoist Personal Access Token.', 'todoistberg'); ?></p>
                    
                    <div class="todoistberg-token-section">
                        <label for="todoist_token"><?php _e('Personal Access Token:', 'todoistberg'); ?></label>
                        <input type="password" id="todoist_token" name="todoist_token" value="<?php echo esc_attr($token); ?>" class="regular-text" />
                        <p class="description">
                            <?php _e('Get your token from <a href="https://app.todoist.com/app/settings/integrations/developer" target="_blank">Todoist Developer Settings</a>', 'todoistberg'); ?>
                        </p>
                    </div>
                    
                    <div class="todoistberg-timezone-section">
                        <label for="todoist_timezone"><?php _e('Timezone:', 'todoistberg'); ?></label>
                        <select id="todoist_timezone" name="todoist_timezone" class="regular-text">
                            <?php
                            $timezones = timezone_identifiers_list();
                            foreach ($timezones as $tz) {
                                $selected = ($tz === $timezone) ? 'selected' : '';
                                echo '<option value="' . esc_attr($tz) . '" ' . $selected . '>' . esc_html($tz) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">
                            <?php _e('Select your timezone for accurate completion statistics. Current timezone: ' . $timezone, 'todoistberg'); ?>
                        </p>
                    </div>
                    
                    <div class="todoistberg-actions">
                        <button type="button" id="save_settings" class="button button-primary">
                            <?php _e('Save Settings', 'todoistberg'); ?>
                        </button>
                        <button type="button" id="test_connection" class="button button-secondary">
                            <?php _e('Test Connection', 'todoistberg'); ?>
                        </button>
                    </div>
                    
                    <div id="connection_status" class="todoistberg-status">
                        <?php if ($connection_status['success']): ?>
                            <div class="notice notice-success">
                                <p><?php _e('✅ Connection successful! Your Todoist account is connected.', 'todoistberg'); ?></p>
                            </div>
                        <?php elseif ($token): ?>
                            <div class="notice notice-error">
                                <p><?php _e('❌ Connection failed. Please check your token.', 'todoistberg'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="todoistberg-card">
                    <h2><?php _e('Available Blocks', 'todoistberg'); ?></h2>
                    <ul>
                        <li><strong>Todo List:</strong> <?php _e('Display tasks from a specific project', 'todoistberg'); ?></li>
                        <li><strong>Todo Form:</strong> <?php _e('Add new tasks to a project', 'todoistberg'); ?></li>
                        <li><strong>Todo Stats:</strong> <?php _e('Show task statistics and progress', 'todoistberg'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .todoistberg-admin-container {
            max-width: 800px;
        }
        .todoistberg-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .todoistberg-token-section {
            margin: 20px 0;
        }
        .todoistberg-token-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .todoistberg-token-section input {
            width: 100%;
            max-width: 400px;
        }
        .todoistberg-actions {
            margin: 20px 0;
        }
        .todoistberg-actions button {
            margin-right: 10px;
        }
        .todoistberg-status {
            margin-top: 20px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#save_settings').on('click', function() {
                var token = $('#todoist_token').val();
                var timezone = $('#todoist_timezone').val();
                var button = $(this);
                var originalText = button.text();
                
                button.prop('disabled', true).text('Saving...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'todoistberg_save_settings',
                        token: token,
                        timezone: timezone,
                        nonce: '<?php echo wp_create_nonce('todoistberg_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Settings saved successfully!');
                        } else {
                            alert('Error saving settings: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error saving settings. Please try again.');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
            
            $('#test_connection').on('click', function() {
                var button = $(this);
                var originalText = button.text();
                
                button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'todoistberg_test_connection',
                        nonce: '<?php echo wp_create_nonce('todoistberg_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#connection_status').html('<div class="notice notice-success"><p>✅ Connection successful! Your Todoist account is connected.</p></div>');
                        } else {
                            $('#connection_status').html('<div class="notice notice-error"><p>❌ Connection failed: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#connection_status').html('<div class="notice notice-error"><p>❌ Connection test failed. Please try again.</p></div>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save token via AJAX
     */
    public function save_token_ajax() {
        check_ajax_referer('todoistberg_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $token = sanitize_text_field($_POST['token']);
        update_option('todoistberg_token', $token);
        
        wp_send_json_success('Token saved successfully');
    }
    
    /**
     * Save settings via AJAX
     */
    public function save_settings_ajax() {
        check_ajax_referer('todoistberg_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $token = sanitize_text_field($_POST['token']);
        $timezone = sanitize_text_field($_POST['timezone']);
        
        update_option('todoistberg_token', $token);
        update_option('todoistberg_timezone', $timezone);
        
        wp_send_json_success('Settings saved successfully');
    }
    
    /**
     * Test connection via AJAX
     */
    public function test_connection_ajax() {
        check_ajax_referer('todoistberg_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $result = $this->test_connection();
        
        if ($result['success']) {
            wp_send_json_success('Connection successful');
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Get Todoist token
     */
    public function get_todoist_token() {
        return get_option('todoistberg_token', '');
    }
    
    /**
     * Get timezone setting
     */
    public function get_timezone() {
        return get_option('todoistberg_timezone', 'UTC');
    }
    
    /**
     * Test Todoist connection
     */
    public function test_connection() {
        $token = $this->get_todoist_token();
        
        if (empty($token)) {
            return array('success' => false, 'message' => 'No token provided');
        }
        
        $response = wp_remote_post('https://api.todoist.com/sync/v9/sync', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => array(
                'sync_token' => '*',
                'resource_types' => '["projects"]'
            )
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array('success' => true, 'message' => 'Connection successful');
        } else {
            return array('success' => false, 'message' => 'HTTP ' . $status_code . ': ' . wp_remote_retrieve_response_message($response));
        }
    }
    
    /**
     * Get projects list for block settings
     */
    public function get_projects_list() {
        $token = $this->get_todoist_token();
        
        if (empty($token)) {
            return array();
        }
        
        $response = wp_remote_post('https://api.todoist.com/sync/v9/sync', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => array(
                'sync_token' => '*',
                'resource_types' => '["projects"]'
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $projects = isset($data['projects']) ? $data['projects'] : array();
        
        $projects_list = array();
        foreach ($projects as $project) {
            $projects_list[] = array(
                'value' => $project['id'],
                'label' => $project['name']
            );
        }
        
        return $projects_list;
    }
    
    /**
     * Render Todo List block
     */
    public function render_todo_list_block($attributes) {
        $project_id = $attributes['projectId'] ?? '';
        $max_items = $attributes['maxItems'] ?? 10;
        $show_completed = $attributes['showCompleted'] ?? false;
        $title = $attributes['title'] ?? '';
        
        $tasks = $this->get_tasks($project_id, $max_items, $show_completed);
        
        ob_start();
        ?>
        <div class="todoistberg-todo-list" data-project-id="<?php echo esc_attr($project_id); ?>">
            <?php if (!empty($title)): ?>
                <h3 class="todoistberg-title"><?php echo esc_html($title); ?></h3>
            <?php endif; ?>
            
            <?php if (empty($tasks)): ?>
                <p class="todoistberg-no-tasks"><?php _e('No tasks found.', 'todoistberg'); ?></p>
            <?php else: ?>
                <ul class="todoistberg-tasks">
                    <?php foreach ($tasks as $task): ?>
                        <li class="todoistberg-task <?php echo $task['completed'] ? 'completed' : ''; ?>" data-task-id="<?php echo esc_attr($task['id']); ?>">
                            <span class="todoistberg-task-content"><?php echo esc_html($task['content']); ?></span>
                            <?php if ($task['due']): ?>
                                <span class="todoistberg-task-due"><?php echo esc_html($task['due']['date']); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Todo Form block
     */
    public function render_todo_form_block($attributes) {
        // Check if user is an administrator
        if (!current_user_can('manage_options')) {
            return '';
        }
        
        $project_id = $attributes['projectId'] ?? '';
        $button_text = $attributes['buttonText'] ?? __('Add Task', 'todoistberg');
        $placeholder = $attributes['placeholder'] ?? __('Enter task description...', 'todoistberg');
        
        ob_start();
        ?>
        <div class="todoistberg-todo-form" data-project-id="<?php echo esc_attr($project_id); ?>">
            <form class="todoistberg-form">
                <input type="text" class="todoistberg-task-input" placeholder="<?php echo esc_attr($placeholder); ?>" required />
                <button type="submit" class="todoistberg-submit-btn" data-original-text="<?php echo esc_attr($button_text); ?>"><?php echo esc_html($button_text); ?></button>
            </form>
            <div class="todoistberg-form-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render Todo Stats block
     */
    public function render_todo_stats_block($attributes) {
        $show_today = $attributes['showToday'] ?? true;
        $show_week = $attributes['showWeek'] ?? true;
        $show_month = $attributes['showMonth'] ?? true;
        $show_past_due = $attributes['showPastDue'] ?? false;
        $number_color = $attributes['numberColor'] ?? '#007cba';
        
        $stats = $this->get_stats($show_today, $show_week, $show_month, $show_past_due);
        
        ob_start();
        ?>
        <div class="todoistberg-todo-stats">
            <h3 class="todoistberg-stats-title"><?php _e('Todoist Completion Statistics', 'todoistberg'); ?></h3>
            <div class="todoistberg-stats-grid">
                <?php if ($show_today && isset($stats['today'])): ?>
                    <div class="todoistberg-stat-item">
                        <span class="todoistberg-stat-number" style="color: <?php echo esc_attr($number_color); ?>"><?php echo esc_html($stats['today']); ?></span>
                        <span class="todoistberg-stat-label"><?php _e('Completed Today', 'todoistberg'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_week && isset($stats['week'])): ?>
                    <div class="todoistberg-stat-item">
                        <span class="todoistberg-stat-number" style="color: <?php echo esc_attr($number_color); ?>"><?php echo esc_html($stats['week']); ?></span>
                        <span class="todoistberg-stat-label"><?php _e('Completed This Week', 'todoistberg'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_month && isset($stats['month'])): ?>
                    <div class="todoistberg-stat-item">
                        <span class="todoistberg-stat-number" style="color: <?php echo esc_attr($number_color); ?>"><?php echo esc_html($stats['month']); ?></span>
                        <span class="todoistberg-stat-label"><?php _e('Completed This Month', 'todoistberg'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_past_due && isset($stats['pastDue'])): ?>
                    <div class="todoistberg-stat-item todoistberg-stat-past-due">
                        <span class="todoistberg-stat-number"><?php echo esc_html($stats['pastDue']); ?></span>
                        <span class="todoistberg-stat-label"><?php _e('Past Due Tasks', 'todoistberg'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get tasks from Todoist API
     */
    public function get_tasks($project_id = '', $max_items = 10, $show_completed = false) {
        $token = $this->get_todoist_token();
        
        if (empty($token)) {
            return array();
        }
        
        $user_timezone = $this->get_timezone();
        
        // Get current date in user's timezone for filtering past-due tasks
        $today = new DateTime('today', new DateTimeZone($user_timezone));
        $today_string = $today->format('Y-m-d');
        
        $response = wp_remote_post('https://api.todoist.com/sync/v9/sync', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => array(
                'sync_token' => '*',
                'resource_types' => '["items"]'
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $all_tasks = isset($data['items']) ? $data['items'] : array();
        
        $tasks = array();
        foreach ($all_tasks as $task) {
            // Filter by project if specified (skip filtering if project_id is empty or 'all')
            if (!empty($project_id) && $project_id !== 'all' && $task['project_id'] != $project_id) {
                continue;
            }
            
            // Filter by completion status
            if (!$show_completed && $task['checked'] == 1) {
                continue;
            }
            
            // Filter out past-due tasks
            if (isset($task['due']) && $task['due'] && isset($task['due']['date'])) {
                $due_date = $task['due']['date'];
                
                // Extract just the date part if it's a full datetime
                if (strpos($due_date, 'T') !== false) {
                    $due_date = substr($due_date, 0, 10);
                }
                
                // Skip tasks that are past due (due date is before today)
                if ($due_date < $today_string) {
                    continue;
                }
            }
            
            // Process due date to use user's timezone
            $due_info = null;
            if (isset($task['due']) && $task['due']) {
                $due_info = $task['due'];
                
                // Convert due date to user's timezone for display
                if (isset($due_info['date'])) {
                    try {
                        // If it's a full datetime, convert to user timezone
                        if (strpos($due_info['date'], 'T') !== false) {
                            $due_date = new DateTime($due_info['date'], new DateTimeZone('UTC'));
                            $due_date->setTimezone(new DateTimeZone($user_timezone));
                            $due_info['date'] = $due_date->format('Y-m-d');
                            $due_info['datetime'] = $due_date->format('Y-m-d H:i:s');
                        }
                        // If it's just a date, keep as is but add timezone info
                        else {
                            $due_info['timezone'] = $user_timezone;
                        }
                    } catch (Exception $e) {
                        // If date parsing fails, keep original
                    }
                }
            }
            
            $tasks[] = array(
                'id' => $task['id'],
                'content' => $task['content'],
                'completed' => $task['checked'] == 1,
                'due' => $due_info
            );
            
            if (count($tasks) >= $max_items) {
                break;
            }
        }
        
        return $tasks;
    }
    
    /**
     * Get statistics from Todoist API
     */
    public function get_stats($show_today = true, $show_week = true, $show_month = true, $show_past_due = false) {
        error_log('🔄 Todoistberg: get_stats called');
        
        $token = $this->get_todoist_token();
        $user_timezone = $this->get_timezone();
        
        if (empty($token)) {
            error_log('❌ Todoistberg: No token found');
            return array();
        }
        
        error_log('✅ Todoistberg: Token found, using timezone: ' . $user_timezone);
        error_log('✅ Todoistberg: Fetching activity log...');
        
        $stats = array();
        
        // Fetch activity log for completed tasks
        $activity_events = $this->get_activity_log();
        
        if ($show_today) {
            $today_count = 0;
            // Use user's timezone for "today"
            $today = new DateTime('today', new DateTimeZone($user_timezone));
            $today_start = $today->format('Y-m-d\T00:00:00');
            $today_end = $today->format('Y-m-d\T23:59:59');
            
            // Convert to UTC for comparison with API data
            $today_start_utc = new DateTime($today_start, new DateTimeZone($user_timezone));
            $today_start_utc->setTimezone(new DateTimeZone('UTC'));
            $today_end_utc = new DateTime($today_end, new DateTimeZone($user_timezone));
            $today_end_utc->setTimezone(new DateTimeZone('UTC'));
            
            $today_start_str = $today_start_utc->format('Y-m-d\TH:i:s\Z');
            $today_end_str = $today_end_utc->format('Y-m-d\TH:i:s\Z');
            
            error_log('📅 Todoistberg: Today in ' . $user_timezone . ': ' . $today_start . ' to ' . $today_end);
            error_log('📅 Todoistberg: Today in UTC: ' . $today_start_str . ' to ' . $today_end_str);
            
            foreach ($activity_events as $event) {
                if ($event['event_type'] === 'completed' && 
                    isset($event['event_date']) && 
                    $event['event_date'] >= $today_start_str && 
                    $event['event_date'] <= $today_end_str) {
                    $today_count++;
                    error_log('✅ Todoistberg: Found task completed today at: ' . $event['event_date']);
                }
            }
            $stats['today'] = $today_count;
            error_log('📊 Todoistberg: Today count: ' . $today_count);
        }
        
        if ($show_week) {
            $week_count = 0;
            // Use user's timezone for week calculation
            $week_start = new DateTime('monday this week', new DateTimeZone($user_timezone));
            $week_end = new DateTime('sunday this week 23:59:59', new DateTimeZone($user_timezone));
            
            // Convert to UTC for comparison
            $week_start_utc = clone $week_start;
            $week_start_utc->setTimezone(new DateTimeZone('UTC'));
            $week_end_utc = clone $week_end;
            $week_end_utc->setTimezone(new DateTimeZone('UTC'));
            
            $week_start_str = $week_start_utc->format('Y-m-d\TH:i:s\Z');
            $week_end_str = $week_end_utc->format('Y-m-d\TH:i:s\Z');
            
            error_log('📅 Todoistberg: Week in ' . $user_timezone . ': ' . $week_start->format('Y-m-d H:i:s') . ' to ' . $week_end->format('Y-m-d H:i:s'));
            error_log('📅 Todoistberg: Week in UTC: ' . $week_start_str . ' to ' . $week_end_str);
            
            foreach ($activity_events as $event) {
                if ($event['event_type'] === 'completed' && 
                    isset($event['event_date']) && 
                    $event['event_date'] >= $week_start_str && 
                    $event['event_date'] <= $week_end_str) {
                    $week_count++;
                    error_log('✅ Todoistberg: Found task completed this week at: ' . $event['event_date']);
                }
            }
            $stats['week'] = $week_count;
            error_log('📊 Todoistberg: Week count: ' . $week_count);
        }
        
        if ($show_month) {
            $month_count = 0;
            // Use user's timezone for month calculation
            $month_start = new DateTime('first day of this month', new DateTimeZone($user_timezone));
            $month_end = new DateTime('last day of this month 23:59:59', new DateTimeZone($user_timezone));
            
            // Convert to UTC for comparison
            $month_start_utc = clone $month_start;
            $month_start_utc->setTimezone(new DateTimeZone('UTC'));
            $month_end_utc = clone $month_end;
            $month_end_utc->setTimezone(new DateTimeZone('UTC'));
            
            $month_start_str = $month_start_utc->format('Y-m-d\TH:i:s\Z');
            $month_end_str = $month_end_utc->format('Y-m-d\TH:i:s\Z');
            
            error_log('📅 Todoistberg: Month in ' . $user_timezone . ': ' . $month_start->format('Y-m-d H:i:s') . ' to ' . $month_end->format('Y-m-d H:i:s'));
            error_log('📅 Todoistberg: Month in UTC: ' . $month_start_str . ' to ' . $month_end_str);
            
            foreach ($activity_events as $event) {
                if ($event['event_type'] === 'completed' && 
                    isset($event['event_date']) && 
                    $event['event_date'] >= $month_start_str && 
                    $event['event_date'] <= $month_end_str) {
                    $month_count++;
                    error_log('✅ Todoistberg: Found task completed this month at: ' . $event['event_date']);
                }
            }
            $stats['month'] = $month_count;
            error_log('📊 Todoistberg: Month count: ' . $month_count);
        }
        
        if ($show_past_due) {
            $past_due_count = $this->get_past_due_count();
            $stats['pastDue'] = $past_due_count;
            error_log('📊 Todoistberg: Past due count: ' . $past_due_count);
        }
        
        return $stats;
    }
    
    /**
     * Get activity log from Todoist API for completed tasks
     */
    public function get_activity_log($limit = 100) {
        error_log('🔄 Todoistberg: get_activity_log called');
        
        $token = $this->get_todoist_token();
        
        if (empty($token)) {
            error_log('❌ Todoistberg: No token in get_activity_log');
            return array();
        }
        
        error_log('📤 Todoistberg: Making API call to Todoist Activity API...');
        
        // Use Todoist Activity API to get completed events
        $response = wp_remote_get("https://api.todoist.com/sync/v9/activity/get?event_type=completed&limit=" . $limit, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('❌ Todoistberg: WP Error in API call: ' . $response->get_error_message());
            return array();
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        error_log('📥 Todoistberg: Activity API response status: ' . $status_code);
        
        if ($status_code !== 200) {
            error_log('❌ Todoistberg: Activity API error status: ' . $status_code);
            error_log('📄 Todoistberg: Activity API response body: ' . wp_remote_retrieve_body($response));
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $events = isset($data['events']) ? $data['events'] : array();
        
        error_log('📊 Todoistberg: Activity API response: ' . count($events) . ' events received');
        
        if (count($events) > 0) {
            error_log('📋 Todoistberg: Sample activity event: ' . print_r($events[0], true));
        }
        
        return $events;
    }
    
    /**
     * Get count of past-due tasks
     */
    public function get_past_due_count() {
        error_log('🔄 Todoistberg: get_past_due_count called');
        
        $token = $this->get_todoist_token();
        $user_timezone = $this->get_timezone();
        
        if (empty($token)) {
            error_log('❌ Todoistberg: No token in get_past_due_count');
            return 0;
        }
        
        // Get all active (uncompleted) tasks
        $response = wp_remote_post('https://api.todoist.com/sync/v9/sync', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => array(
                'sync_token' => '*',
                'resource_types' => '["items"]'
            )
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log('❌ Todoistberg: Failed to fetch tasks for past-due count');
            return 0;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $all_tasks = isset($data['items']) ? $data['items'] : array();
        
        $now = new DateTime('now', new DateTimeZone($user_timezone));
        $today = $now->format('Y-m-d');
        $past_due_count = 0;
        
        error_log('📅 Todoistberg: Checking for tasks past due before: ' . $today . ' (in ' . $user_timezone . ')');
        
        foreach ($all_tasks as $task) {
            // Skip completed tasks
            if ($task['checked'] == 1) {
                continue;
            }
            
            // Check if task has a due date
            if (isset($task['due']) && !empty($task['due']['date'])) {
                $due_date = $task['due']['date'];
                
                // Handle both date and datetime formats
                if (strpos($due_date, 'T') !== false) {
                    // Full datetime - extract just the date part
                    $due_date = substr($due_date, 0, 10);
                }
                
                // Check if due date is before today
                if ($due_date < $today) {
                    $past_due_count++;
                    error_log('⏰ Todoistberg: Found past due task: "' . $task['content'] . '" due ' . $due_date);
                }
            }
        }
        
        error_log('📊 Todoistberg: Total past due tasks: ' . $past_due_count);
        return $past_due_count;
    }
    
    /**
     * Get stats via AJAX
     */
    public function get_stats_ajax() {
        error_log('🔄 Todoistberg: get_stats_ajax called');
        
        check_ajax_referer('todoistberg_frontend_nonce', 'nonce');
        
        $show_today = isset($_POST['show_today']) ? (bool) $_POST['show_today'] : true;
        $show_week = isset($_POST['show_week']) ? (bool) $_POST['show_week'] : true;
        $show_month = isset($_POST['show_month']) ? (bool) $_POST['show_month'] : true;
        $show_past_due = isset($_POST['show_past_due']) ? (bool) $_POST['show_past_due'] : false;
        
        error_log('📊 Todoistberg: Show settings - Today: ' . ($show_today ? 'true' : 'false') . ', Week: ' . ($show_week ? 'true' : 'false') . ', Month: ' . ($show_month ? 'true' : 'false') . ', Past Due: ' . ($show_past_due ? 'true' : 'false'));
        
        $stats = $this->get_stats($show_today, $show_week, $show_month, $show_past_due);
        
        error_log('📊 Todoistberg: Stats calculated: ' . print_r($stats, true));
        
        wp_send_json_success($stats);
    }
    
    /**
     * Add task via AJAX
     */
    public function add_task_ajax() {
        check_ajax_referer('todoistberg_frontend_nonce', 'nonce');
        
        // Check if user is an administrator
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions. Only administrators can add tasks.');
        }
        
        $task_content = sanitize_text_field($_POST['task_content']);
        $project_id = sanitize_text_field($_POST['project_id']);
        
        if (empty($task_content)) {
            wp_send_json_error('Task content is required');
        }
        
        $token = $this->get_todoist_token();
        if (empty($token)) {
            wp_send_json_error('Todoist token not configured');
        }
        
        // Use REST API v2 for adding tasks (more reliable than SyncAPI for this operation)
        $response = wp_remote_post('https://api.todoist.com/rest/v2/tasks', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'content' => $task_content,
                'project_id' => $project_id
            ))
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to add task: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            wp_send_json_success('Task added successfully');
        } else {
            wp_send_json_error('Failed to add task. HTTP ' . $status_code);
        }
    }
    
    /**
     * Toggle task completion via AJAX
     */
    public function toggle_task_ajax() {
        check_ajax_referer('todoistberg_frontend_nonce', 'nonce');
        
        $task_id = sanitize_text_field($_POST['task_id']);
        $completed = (bool) $_POST['completed'];
        
        if (empty($task_id)) {
            wp_send_json_error('Task ID is required');
        }
        
        $token = $this->get_todoist_token();
        if (empty($token)) {
            wp_send_json_error('Todoist token not configured');
        }
        
        // Use REST API v2 for task completion
        $endpoint = $completed ? 'close' : 'reopen';
        $response = wp_remote_post("https://api.todoist.com/rest/v2/tasks/{$task_id}/{$endpoint}", array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token
            )
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to update task: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 204) {
            wp_send_json_success('Task updated successfully');
        } else {
            wp_send_json_error('Failed to update task. HTTP ' . $status_code);
        }
    }
    
    /**
     * Get tasks via AJAX
     */
    public function get_tasks_ajax() {
        check_ajax_referer('todoistberg_frontend_nonce', 'nonce');
        
        $project_id = sanitize_text_field($_POST['project_id']);
        
        $tasks = $this->get_tasks($project_id, 50, false);
        
        wp_send_json_success(array('tasks' => $tasks));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('todoistberg/v1', '/tasks', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tasks_rest'),
            'permission_callback' => '__return_true',
            'args' => array(
                'project_id' => array(
                    'type' => 'string',
                    'default' => '',
                ),
                'max_items' => array(
                    'type' => 'integer',
                    'default' => 10,
                ),
                'show_completed' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
        ));
    }
    
    /**
     * Get tasks via REST API
     */
    public function get_tasks_rest($request) {
        $project_id = $request->get_param('project_id');
        $max_items = $request->get_param('max_items');
        $show_completed = $request->get_param('show_completed');
        
        $tasks = $this->get_tasks($project_id, $max_items, $show_completed);
        
        return rest_ensure_response($tasks);
    }
}

// Initialize the plugin
new Todoistberg_Plugin();
