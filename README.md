# Todoistberg - Todoist Gutenberg Blocks

A comprehensive WordPress plugin that provides Gutenberg blocks for integrating Todoist functionality into your WordPress site.

## Features

- **Todo List Block**: Display tasks from specific Todoist projects
- **Todo Stats Block**: Show task statistics and progress
- **Secure API Integration**: Uses Todoist Personal Access Token for secure authentication
- **Responsive Design**: Mobile-friendly blocks that work on all devices
- **Real-time Updates**: Interactive features for task management

## Future Features
- **Site Admin Completion**: A site admin can check off the boxes in the list display to complete tasks in the Todoist app.
- **Monthly Productivity Report**: Downloads stats for a given month/year and freezes them in-place so that they're not dependent on the API.

## Installation

### Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Node.js and npm (for development)

### Setup

1. **Clone or download the plugin** to your WordPress plugins directory:
   ```
   wp-content/plugins/todoistberg/
   ```

2. **Install dependencies** (for development):
   ```bash
   cd wp-content/plugins/todoistberg/
   npm install
   ```

3. **Build the assets**:
   ```bash
   npm run build
   ```

4. **Activate the plugin** in WordPress admin

5. **Configure your Todoist API token**:
   - Go to Settings → Todoistberg
   - Get your Personal Access Token from [Todoist Developer Settings](https://app.todoist.com/app/settings/integrations/developer)
   - Enter the token and test the connection

## Usage

### Todo List Block

Display tasks from a specific Todoist project:

1. Add the "Todoist Task List" block to your page/post
2. Configure the block settings:
   - **Project**: Select which Todoist project to display tasks from
   - **Maximum Items**: Set how many tasks to show (1-50)
   - **Show Completed Tasks**: Toggle to include completed tasks
   - **Title**: Optional custom title for the block

### Todo Form Block

Allow administrators to add tasks to your Todoist projects:

1. Add the "Todoist Task Form" block to your page/post
2. Configure the block settings:
   - **Project**: Select which Todoist project to add tasks to
   - **Button Text**: Customize the submit button text
   - **Placeholder Text**: Customize the input placeholder

**Note**: This block is only visible to administrators on the frontend for security purposes.

### Todo Stats Block

Display completed task statistics:

1. Add the "Todoist Completion Statistics" block to your page/post
2. Configure which statistics to show:
   - **Show Today**: Display tasks completed today
   - **Show This Week**: Display tasks completed this week
   - **Show This Month**: Display tasks completed this month

## API Integration

The plugin integrates with the Todoist REST API v2. All API calls are made server-side for security.

### Supported API Endpoints

- `GET /projects` - Fetch user's projects
- `GET /tasks` - Fetch tasks with filtering options
- `POST /tasks` - Create new tasks
- `POST /tasks/:id/close` - Complete tasks
- `POST /tasks/:id/reopen` - Reopen tasks

### Security

- API tokens are stored securely in WordPress options
- All API calls include proper authentication headers
- Nonces are used for all AJAX requests
- Input sanitization and validation on all user inputs

## Development

### Project Structure

```
todoistberg/
├── src/
│   ├── blocks/
│   │   ├── todo-list.js
│   │   ├── todo-form.js
│   │   └── todo-stats.js
│   ├── index.js
│   ├── frontend.js
│   └── style.scss
├── build/          # Generated assets
├── todoistberg.php # Main plugin file
├── package.json
└── README.md
```

### Available Scripts

- `npm run build` - Build production assets
- `npm run start` - Start development mode with hot reloading
- `npm run lint:js` - Lint JavaScript files
- `npm run lint:css` - Lint CSS files
- `npm run format` - Format code with Prettier

### Adding New Blocks

1. Create a new block component in `src/blocks/`
2. Register the block in `src/index.js`
3. Add styles to `src/style.scss`
4. Update the main plugin file if needed

### Customization

#### Styling

The plugin uses SCSS for styling. Main variables are defined in `src/style.scss`:

```scss
$todoistberg-primary: #db4c3f;
$todoistberg-secondary: #f5f5f5;
$todoistberg-text: #333;
$todoistberg-border: #ddd;
```

#### Hooks and Filters

The plugin provides several WordPress hooks for customization:

```php
// Modify task data before display
add_filter('todoistberg_task_data', function($task) {
    // Modify task data
    return $task;
});

// Customize API response handling
add_action('todoistberg_api_response', function($response, $endpoint) {
    // Handle API response
}, 10, 2);
```

## Troubleshooting

### Common Issues

1. **"Please configure your Todoist API token"**
   - Go to Settings → Todoistberg
   - Enter your Personal Access Token
   - Test the connection

2. **"Connection failed"**
   - Verify your token is correct
   - Check your internet connection
   - Ensure the token has proper permissions

3. **Blocks not appearing**
   - Ensure the plugin is activated
   - Check for JavaScript errors in browser console
   - Verify assets are built (`npm run build`)

4. **Tasks not loading**
   - Check if the project ID is correct
   - Verify the project exists in your Todoist account
   - Check API rate limits

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please:
1. Check the troubleshooting section above
2. Search existing issues on GitHub
3. Create a new issue with detailed information

## Changelog

### 1.0.0
- Initial release
- Todo List, Todo Form, and Todo Stats blocks
- Todoist API integration
- Admin settings page
- Responsive design
- Frontend interactivity
