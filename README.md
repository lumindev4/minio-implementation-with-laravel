## Configure environment variables
 - first create .env from .env examples

 ## for MinIO please add this variable to env
 ```
    FILESYSTEM_DISK=minio

    MINIO_ENDPOINT=http://127.0.0.1:9000
    MINIO_ACCESS_KEY=minioadmin
    MINIO_SECRET_KEY=minioadmin123
    MINIO_REGION=us-east-1
    MINIO_BUCKET=laravel-test
```

## then update the config/filesystems.php file with minion storage desk
    ```
         'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_ACCESS_KEY'),
            'secret' => env('MINIO_SECRET_KEY'),
            'region' => env('MINIO_REGION'),
            'bucket' => env('MINIO_BUCKET'),
            'endpoint' => env('MINIO_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'throw' => true,
        ],
    ```


## install a new package
 # if you see -S3 Flysystem adapter is still NOT installed (or not loaded correctly).

```
    composer require league/flysystem-aws-s3-v3:^3.0
```


### Min IO Local setup
 #for mac plesae run this command to install minio into your pc
    ```
        brew install minio/stable/minio
        brew install minio/stable/mc
    ```
## after this create directory
``` mkdir -p ~/minio/data ```
## and run the minio server using 
```
    minio server ~/minio/data --console-address ":9001"
```

## it show
```
API: http://192.168.0.132:9000  http://127.0.0.1:9000 
   RootUser: minioadmin 
   RootPass: minioadmin 

WebUI: http://192.168.0.132:9001 http://127.0.0.1:9001        
   RootUser: minioadmin 
   RootPass: minioadmin 
```


## please check php ini 
    - upload_max_filesize = as your expected max limit in MB 
    - post_max_size = as your expected max limit in MB 
