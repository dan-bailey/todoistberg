#!/bin/bash

# Todoistberg Installation Script
echo "🚀 Installing Todoistberg dependencies..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

# Install dependencies
echo "📦 Installing npm dependencies..."
npm install

if [ $? -eq 0 ]; then
    echo "✅ Dependencies installed successfully!"
else
    echo "❌ Failed to install dependencies."
    exit 1
fi

# Build assets
echo "🔨 Building assets..."
npm run build

if [ $? -eq 0 ]; then
    echo "✅ Assets built successfully!"
else
    echo "❌ Failed to build assets."
    exit 1
fi

echo ""
echo "🎉 Todoistberg installation complete!"
echo ""
echo "Next steps:"
echo "1. Activate the plugin in WordPress admin"
echo "2. Go to Settings → Todoistberg"
echo "3. Enter your Todoist Personal Access Token"
echo "4. Test the connection"
echo ""
echo "For development:"
echo "- Run 'npm run start' for development mode with hot reloading"
echo "- Run 'npm run build' to build production assets"
echo ""
echo "Happy coding! 🎯"
