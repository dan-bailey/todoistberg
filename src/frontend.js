/**
 * Todoistberg Frontend JavaScript
 */

(function($) {
    'use strict';

    // Initialize when DOM is ready
    $(document).ready(function() {
        TodoistbergFrontend.init();
    });

    const TodoistbergFrontend = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Handle todo form submissions
            $(document).on('submit', '.todoistberg-form', this.handleFormSubmit);
            
            // Handle task completion clicks
            $(document).on('click', '.todoistberg-task', this.handleTaskClick);
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $input = $form.find('.todoistberg-task-input');
            const $button = $form.find('.todoistberg-submit-btn');
            const $message = $form.siblings('.todoistberg-form-message');
            const $container = $form.closest('.todoistberg-todo-form');
            
            const taskContent = $input.val().trim();
            const projectId = $container.data('project-id');
            
            if (!taskContent) {
                TodoistbergFrontend.showMessage($message, 'Please enter a task description.', 'error');
                return;
            }

            // Show loading state
            $button.prop('disabled', true).text('Adding...');
            TodoistbergFrontend.showMessage($message, 'Adding task...', 'loading');

            // Submit task to Todoist
            $.ajax({
                url: todoistbergFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'todoistberg_add_task',
                    task_content: taskContent,
                    project_id: projectId,
                    nonce: todoistbergFrontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        TodoistbergFrontend.showMessage($message, 'Task added successfully!', 'success');
                        $input.val('');
                        
                        // Optionally refresh nearby todo lists
                        TodoistbergFrontend.refreshNearbyLists($container);
                    } else {
                        TodoistbergFrontend.showMessage($message, response.data || 'Failed to add task.', 'error');
                    }
                },
                error: function() {
                    TodoistbergFrontend.showMessage($message, 'Network error. Please try again.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text($button.data('original-text') || 'Add Task');
                }
            });
        },

        handleTaskClick: function(e) {
            const $task = $(this);
            const taskId = $task.data('task-id');
            
            if (!taskId) return;
            
            // Toggle completion state
            const isCompleted = $task.hasClass('completed');
            const newState = !isCompleted;
            
            // Show loading state
            $task.addClass('updating');
            
            $.ajax({
                url: todoistbergFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'todoistberg_toggle_task',
                    task_id: taskId,
                    completed: newState,
                    nonce: todoistbergFrontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (newState) {
                            $task.addClass('completed');
                        } else {
                            $task.removeClass('completed');
                        }
                    } else {
                        // Revert visual state on error
                        if (newState) {
                            $task.removeClass('completed');
                        } else {
                            $task.addClass('completed');
                        }
                        console.error('Failed to update task:', response.data);
                    }
                },
                error: function() {
                    // Revert visual state on error
                    if (newState) {
                        $task.removeClass('completed');
                    } else {
                        $task.addClass('completed');
                    }
                    console.error('Network error updating task');
                },
                complete: function() {
                    $task.removeClass('updating');
                }
            });
        },

        showMessage: function($element, message, type) {
            $element
                .removeClass('success error loading')
                .addClass(type)
                .html('<p>' + message + '</p>')
                .show();
            
            // Auto-hide success messages after 3 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $element.fadeOut();
                }, 3000);
            }
        },

        refreshNearbyLists: function($container) {
            // Find and refresh nearby todo lists within the same page
            const $lists = $('.todoistberg-todo-list').not($container.find('.todoistberg-todo-list'));
            
            if ($lists.length > 0) {
                $lists.each(function() {
                    const $list = $(this);
                    const projectId = $list.data('project-id');
                    
                    if (projectId) {
                        TodoistbergFrontend.refreshTaskList($list, projectId);
                    }
                });
            }
        },

        refreshTaskList: function($list, projectId) {
            const $tasksContainer = $list.find('.todoistberg-tasks');
            const $noTasks = $list.find('.todoistberg-no-tasks');
            
            // Show loading state
            $tasksContainer.html('<li class="todoistberg-loading">Loading...</li>');
            
            $.ajax({
                url: todoistbergFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'todoistberg_get_tasks',
                    project_id: projectId,
                    nonce: todoistbergFrontend.nonce
                },
                success: function(response) {
                    if (response.success && response.data.tasks) {
                        const tasks = response.data.tasks;
                        
                        if (tasks.length === 0) {
                            $tasksContainer.hide();
                            $noTasks.show();
                        } else {
                            $noTasks.hide();
                            $tasksContainer.show();
                            
                            const tasksHtml = tasks.map(function(task) {
                                const completedClass = task.completed ? 'completed' : '';
                                const dueDate = task.due ? 
                                    '<span class="todoistberg-task-due">' + new Date(task.due.date).toLocaleDateString() + '</span>' : '';
                                
                                return '<li class="todoistberg-task ' + completedClass + '" data-task-id="' + task.id + '">' +
                                    '<span class="todoistberg-task-content">' + task.content + '</span>' +
                                    dueDate +
                                    '</li>';
                            }).join('');
                            
                            $tasksContainer.html(tasksHtml);
                        }
                    }
                },
                error: function() {
                    $tasksContainer.html('<li class="todoistberg-error">Failed to load tasks</li>');
                }
            });
        },

        // Utility function to format dates
        formatDate: function(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString();
        },

        // Utility function to debounce API calls
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Make it globally available
    window.TodoistbergFrontend = TodoistbergFrontend;

})(jQuery);
