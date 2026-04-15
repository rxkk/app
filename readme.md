
This simple CLI project

How it's work:
1. you install the project
```bash
composer create-project rxkk/app your-project-name
```

get start help
```bash
./x
```

get the list of commands
```bash
./x code
```

run a specific command
```bash
./x Test::example someArg
```


# MCP

Check list of MCP tools
```bash
echo '{"jsonrpc":"2.0", "id":1, "method":"tools/list","params":{}}' | ./x mcp | python3 -m json.tool
```

Call MySQL::query with pass `SELECT VERSION()` to `sql` argument
```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"MySQL::query","arguments":{"sql":"SELECT VERSION()"}}}' | ./x mcp | python3 -m json.tool
```


How to use it in code.
```php
/**
 * @MCP This is allow use this method in MCP + description it
 *      And this is example of multiline description.
 * @MCP-CONFIRM  # this is will force to ask before run this function 
 * 
 * @param int $someArg description for argument which will added to MCP info
 */
function someFunc($someArg) {}
```
