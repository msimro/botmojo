# BotMojo API Documentation

## Overview
BotMojo provides a RESTful API for interacting with the AI assistant system. All endpoints return JSON responses and require proper authentication.

## Base URL
```
https://api.botmojo.com/v1
```

## Authentication
All API requests require an API key passed in the `Authorization` header:
```
Authorization: Bearer YOUR_API_KEY
```

## Endpoints

### POST /chat
Send a message to the AI assistant.

#### Request
```json
{
  "query": "string",
  "sessionId": "string",
  "context": {
    "timezone": "string",
    "preferences": "object",
    "history": "array"
  }
}
```

#### Response
```json
{
  "success": true,
  "response": {
    "message": "string",
    "agent": "string",
    "confidence": "number",
    "actions": "array"
  },
  "sessionId": "string",
  "timestamp": "string"
}
```

#### Error Response
```json
{
  "success": false,
  "error": {
    "code": "number",
    "message": "string",
    "details": "object"
  }
}
```

### GET /status
Check API system status.

#### Response
```json
{
  "status": "string",
  "version": "string",
  "uptime": "number",
  "timestamp": "string"
}
```

## Rate Limiting
- 100 requests per minute per API key
- 1000 requests per hour per API key

## Error Codes
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 429: Too Many Requests
- 500: Internal Server Error
