# RequestProfiler
Log parser to identify actions with greatest memory usage or time

### Usage
```sh
$ cd RequestProfiler
$ php profiler.php -f <file> -s <sort-option>
```

- file - must be a valid and readable file
- sort-option - can be one of real_time|user_time|system_time|marked_time|memory_usage [Sorted by real-time by default]

#####Downloading the output file
```sh
$ php profiler.php -f <file> -s <sort-option> -d filename.csv
```

### Request Log File
The parser needs a CSV log file in the following order : real_time, system_time, marked_time, memory_usage, uri. Sample log entries would look like:
```sh
0.014,0.011,0.002,0.001,1544096,website/main/index
0.014,0.011,0.002,0,1596984,view/internal/23057
0.015,0.011,0.002,0.001,1544384,website/main/index
```

### Features
- Supports aggregation based on URIs
- Sort by multiple parameters
- Download the output in CSV
