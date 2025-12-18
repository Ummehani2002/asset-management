#!/bin/bash
echo "Starting Laravel Scheduler..."
echo "This will check for delayed tasks every 5 minutes automatically"
echo "Press Ctrl+C to stop"
echo ""
php artisan schedule:work

