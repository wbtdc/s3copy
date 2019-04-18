# s3copy
This script copies data to/from S3-compliant storage (like Wasabi or Amazon S3).

To use it, create a folder named .aws in your home directory, and in that directory
create a file called 'credentials'.  

In that file, copy and paste the following:

```
[s3copy]
aws_access_key_id=YOUR_AWS_ACCESS_KEY
aws_secret_access_key=YOUR_AWS_SECRET_KEY
```

Replace the access and secret keys with your own AWS credentials.

Then create another file in the .aws directory named 'config'. In that file, copy and paste the following:

```
[s3copy]
output=json
```

Optionally replace the region string with your preferred region.
