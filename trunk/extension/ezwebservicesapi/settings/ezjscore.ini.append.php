<?php /*

[ezjscServer]
# permission functions
#FunctionList[]=ezdebug_inspect

# Example urls to test this server functions from a browser:
# <root>/ezjscore/call/ezp::inspect::<...to_be_determined...>
# <root>/ezjscore/call/ezp::fetch_content_list::{"parent_node_id":2}?ContentType=json
# <root>/ezjscore/call/ezp::operation_content_hide::[2]?ContentType=json

[ezjscServer_ezp]
# actual class is ezWebservicesAPIJSCFunctions, but it is built dynamically
# to allow us to introspect existing
Class=ezWebservicesAPIJSCFunctionsExtended
File=extension/ezwebservicesapi/classes/ezwebservicesapijscfunctionsextender.php

# Policies
###Functions[]=ezdebug
###PermissionPrFunction=enabled

*/ ?>