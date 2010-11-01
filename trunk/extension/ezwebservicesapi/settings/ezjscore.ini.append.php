<?php /*

[ezjscServer]
# permission functions
#FunctionList[]=ezdebug_inspect

# Example url to test this server functions:
# <root>/ezjscore/call/ezp::inspect::<...to_be_determined...>

[ezjscServer_ezp]
# actual class is ezWebservicesAPIJSCFunctions, but it is built dynamically
# to allow us to introspect existing 
Class=ezWebservicesAPIJSCFunctionsExtended
File=extension/ezwebservicesapi/classes/ezwebservicesapijscfunctionsextender.php

# Policies
###Functions[]=ezdebug
###PermissionPrFunction=enabled

*/ ?>