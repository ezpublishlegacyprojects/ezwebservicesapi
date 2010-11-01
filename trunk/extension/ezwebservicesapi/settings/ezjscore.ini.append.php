<?php /*

[ezjscServer]
# permission functions
#FunctionList[]=ezdebug_inspect

# Example urls to test this server functions from a browser:
# <root>/ezjscore/call/ezp::inspect::<...to_be_determined...>
# <root>/ezjscore/call/ezp::fetch_content_list::{"parent_node_id":2}?ContentType=json
# <root>/ezjscore/call/ezp::operation_content_hide::{"node_id":2}?ContentType=json

[ezjscServer_ezp]
# actual class is a subclass of ezWebservicesAPIJSCFunctions, it is built
# dynamically to allow us to inject into it new methods exposed as services
Class=ezWebservicesAPIJSCFunctionsExtended
File=extension/ezwebservicesapi/classes/ezwebservicesapijscfunctionsextender.php

# Policies
###Functions[]=ezdebug
###PermissionPrFunction=enabled

*/ ?>