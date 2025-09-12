#!/bin/bash

# Start queue workers for import processing
echo "Starting database queue workers for imports..."

# Start worker for imports queue
php artisan queue:work database \
    --queue=imports \
    --timeout=300 \
    --memory=512 \
    --tries=3 \
    --sleep=3 \
    --verbose &

# Start worker for default queue
php artisan queue:work database \
    --queue=default \
    --timeout=60 \
    --memory=256 \
    --tries=3 \
    --sleep=3 \
    --verbose &

echo "Queue workers started!"
echo "Press Ctrl+C to stop all workers"

# Wait for all background processes
wait
