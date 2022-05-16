# cm4all-wp-impex REST API

> Version v1

## Path Table

| Method | Path | Description |
| --- | --- | --- |
| GET | [/cm4all-wp-impex/v1/export/profile/schema](#getcm4all-wp-impexv1exportprofileschema) |  |
| GET | [/cm4all-wp-impex/v1/export/profile](#getcm4all-wp-impexv1exportprofile) |  |
| GET | [/cm4all-wp-impex/v1/export/profile/{name}](#getcm4all-wp-impexv1exportprofilename) |  |
| GET | [/cm4all-wp-impex/v1/import/profile/schema](#getcm4all-wp-impexv1importprofileschema) |  |
| GET | [/cm4all-wp-impex/v1/import/profile](#getcm4all-wp-impexv1importprofile) |  |
| GET | [/cm4all-wp-impex/v1/import/profile/{name}](#getcm4all-wp-impexv1importprofilename) |  |
| GET | [/cm4all-wp-impex/v1/export/schema](#getcm4all-wp-impexv1exportschema) |  |
| GET | [/cm4all-wp-impex/v1/export](#getcm4all-wp-impexv1export) |  |
| POST | [/cm4all-wp-impex/v1/export](#postcm4all-wp-impexv1export) |  |
| GET | [/cm4all-wp-impex/v1/export/{id}](#getcm4all-wp-impexv1exportid) |  |
| POST | [/cm4all-wp-impex/v1/export/{id}](#postcm4all-wp-impexv1exportid) |  |
| PUT | [/cm4all-wp-impex/v1/export/{id}](#putcm4all-wp-impexv1exportid) |  |
| PATCH | [/cm4all-wp-impex/v1/export/{id}](#patchcm4all-wp-impexv1exportid) |  |
| DELETE | [/cm4all-wp-impex/v1/export/{id}](#deletecm4all-wp-impexv1exportid) |  |
| GET | [/cm4all-wp-impex/v1/export/{id}/slice](#getcm4all-wp-impexv1exportidslice) |  |
| GET | [/cm4all-wp-impex/v1/import/schema](#getcm4all-wp-impexv1importschema) |  |
| GET | [/cm4all-wp-impex/v1/import](#getcm4all-wp-impexv1import) |  |
| POST | [/cm4all-wp-impex/v1/import](#postcm4all-wp-impexv1import) |  |
| GET | [/cm4all-wp-impex/v1/import/{id}](#getcm4all-wp-impexv1importid) |  |
| POST | [/cm4all-wp-impex/v1/import/{id}](#postcm4all-wp-impexv1importid) |  |
| PUT | [/cm4all-wp-impex/v1/import/{id}](#putcm4all-wp-impexv1importid) |  |
| PATCH | [/cm4all-wp-impex/v1/import/{id}](#patchcm4all-wp-impexv1importid) |  |
| DELETE | [/cm4all-wp-impex/v1/import/{id}](#deletecm4all-wp-impexv1importid) |  |
| POST | [/cm4all-wp-impex/v1/import/{id}/slice](#postcm4all-wp-impexv1importidslice) |  |
| POST | [/cm4all-wp-impex/v1/import/{id}/consume](#postcm4all-wp-impexv1importidconsume) |  |
| PUT | [/cm4all-wp-impex/v1/import/{id}/consume](#putcm4all-wp-impexv1importidconsume) |  |
| PATCH | [/cm4all-wp-impex/v1/import/{id}/consume](#patchcm4all-wp-impexv1importidconsume) |  |

## Reference Table

| Name | Path | Description |
| --- | --- | --- |
| postCm4allWpImpexV1Import | [#/components/requestBodies/postCm4allWpImpexV1Import](#componentsrequestbodiespostcm4allwpimpexv1import) |  |
| postCm4allWpImpexV1Export | [#/components/requestBodies/postCm4allWpImpexV1Export](#componentsrequestbodiespostcm4allwpimpexv1export) |  |
| basic | [#/components/securitySchemes/basic](#componentssecurityschemesbasic) |  |

## Path Details

***

### [GET]/cm4all-wp-impex/v1/export/profile/schema

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/export/profile

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/export/profile/{name}

- Security  
basic  

#### Parameters(Query)

```ts
context?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/import/profile/schema

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/import/profile

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/import/profile/{name}

- Security  
basic  

#### Parameters(Query)

```ts
context?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/export/schema

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/export

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [POST]/cm4all-wp-impex/v1/export

- Security  
basic  

#### RequestBody

- multipart/form-data

```ts
{
  // The options used to create the export.
  options?: string
  // The name of the export profile to use.
  profile?: string
  // The human readable name of the export
  name?: string
  // The human readable description of the export
  description?: string
}
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/export/{id}

- Security  
basic  

#### Parameters(Query)

```ts
context?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [POST]/cm4all-wp-impex/v1/export/{id}

- Security  
basic  

#### RequestBody

- multipart/form-data

```ts
{
  // The options used to create the export.
  options?: string
  // The name of the export profile to use.
  profile?: string
  // The human readable name of the export
  name?: string
  // The human readable description of the export
  description?: string
}
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [PUT]/cm4all-wp-impex/v1/export/{id}

- Security  
basic  

#### Parameters(Query)

```ts
options?: string
```

```ts
profile?: string
```

```ts
name?: string
```

```ts
description?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [PATCH]/cm4all-wp-impex/v1/export/{id}

- Security  
basic  

#### Parameters(Query)

```ts
options?: string
```

```ts
profile?: string
```

```ts
name?: string
```

```ts
description?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [DELETE]/cm4all-wp-impex/v1/export/{id}

- Security  
basic  

#### Parameters(Query)

```ts
force?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/export/{id}/slice

- Security  
basic  

#### Parameters(Query)

```ts
context?: string[]
```

```ts
page?: string
```

```ts
per_page?: string
```

```ts
offset?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/import/schema

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/import

- Security  
basic  

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [POST]/cm4all-wp-impex/v1/import

- Security  
basic  

#### RequestBody

- multipart/form-data

```ts
{
  // The options used to create the import.
  options?: string
  // The name of the import profile to use.
  profile?: string
  // The name of the import
  name?: string
  // The description of the import
  description?: string
}
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [GET]/cm4all-wp-impex/v1/import/{id}

- Security  
basic  

#### Parameters(Query)

```ts
context?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [POST]/cm4all-wp-impex/v1/import/{id}

- Security  
basic  

#### RequestBody

- multipart/form-data

```ts
{
  // The options used to create the import.
  options?: string
  // The name of the import profile to use.
  profile?: string
  // The name of the import
  name?: string
  // The description of the import
  description?: string
}
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [PUT]/cm4all-wp-impex/v1/import/{id}

- Security  
basic  

#### Parameters(Query)

```ts
options?: string
```

```ts
profile?: string
```

```ts
name?: string
```

```ts
description?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [PATCH]/cm4all-wp-impex/v1/import/{id}

- Security  
basic  

#### Parameters(Query)

```ts
options?: string
```

```ts
profile?: string
```

```ts
name?: string
```

```ts
description?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [DELETE]/cm4all-wp-impex/v1/import/{id}

- Security  
basic  

#### Parameters(Query)

```ts
force?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [POST]/cm4all-wp-impex/v1/import/{id}/slice

- Security  
basic  

#### RequestBody

- multipart/form-data

```ts
{
  // slice position column in database
  position: string
}
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [POST]/cm4all-wp-impex/v1/import/{id}/consume

- Security  
basic  

#### RequestBody

- multipart/form-data

```ts
{
  // Offset at which to start consuming
  offset?: string
  // Lmit at which to end consuming
  limit?: string
}
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [PUT]/cm4all-wp-impex/v1/import/{id}/consume

- Security  
basic  

#### Parameters(Query)

```ts
offset?: string
```

```ts
limit?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

***

### [PATCH]/cm4all-wp-impex/v1/import/{id}/consume

- Security  
basic  

#### Parameters(Query)

```ts
offset?: string
```

```ts
limit?: string
```

#### Responses

- 200 OK

- 400 Bad Request

- 404 Not Found

## References

### #/components/requestBodies/postCm4allWpImpexV1Import

- multipart/form-data

```ts
{
  // The options used to create the import.
  options?: string
  // The name of the import profile to use.
  profile?: string
  // The name of the import
  name?: string
  // The description of the import
  description?: string
}
```

### #/components/requestBodies/postCm4allWpImpexV1Export

- multipart/form-data

```ts
{
  // The options used to create the export.
  options?: string
  // The name of the export profile to use.
  profile?: string
  // The human readable name of the export
  name?: string
  // The human readable description of the export
  description?: string
}
```

### #/components/securitySchemes/basic

```ts
{
  "type": "http",
  "scheme": "basic"
}
```
