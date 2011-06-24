/**
 eZPTemplateProxy class

 @version $Id$
 @author G. Giunta
 @copyright (C) G. Giunta 2011
 @license code licensed under the GNU GPL 2.0: see README

 @todo document perms needed:
       - ws fetch_any
       - other?
 @todo implement obj cache so that they are only retrieved once
 @todo implement a non-ws method to retrieve fetch results definitions, ezpo class defs (faster)
 @todo allow retrieving objects whose keys are composite
 @todo add an async version of ajax calls, where user provides a callback function
 @todo test on chrome, opera, ie
 @todo test encoding depth of results when getting a nested-array attribute or in fetches
*/

/**
 @todo ideally we should make sure that the json module is loaded here
 @todo add config options to enable obj cache or not
*/
function eZPTemplateProxy( options )
{
    this.ws_transport = options.ws_transport;
    this.transport_url = options.transport_url;
    this.Y = options.Y;

    /// @todo make these two vars static class vars by putting them into prototype
    this.views_defs = [];
    this.obj_defs = [];
}

var p = eZPTemplateProxy.prototype;

/**
 Executes an ezp tpl fetch via an ajax call (sync, unless callback func is provided)
 @todo use specific fetch functions for better perms mgmt?
*/
p.fetch = function( module, view, hash, callback )
{
    if ( this.ws_transport == 'ezjscore' || this.ws_transport == 'ggwebservices' )
    {
        if ( this.ws_transport == 'ezjscore' )
        {
            var postData = 'ezjscServer_function_arguments=' + 'ezp::fetch::' + module + '::' + view + '::' + this.Y.JSON.stringify( hash );
        }
        else
        {
            var postData = this.Y.JSON.stringify( { "method": "ezp.fetchall", "params": [ module, view, hash ], "id": 1 } );
        }

        var result = this._sendRequest( postData, callback );

        // sync processing
        if ( callback === undefined ) /// @todo test if result is not null / undefined
        {
            // recover expected type of fetch function (and cache it)
            var key = module + '_' + view;
            if ( this.views_defs[key] === undefined )
            {
                var classname =  this.fetchFetchDef( module, view );
                this.views_defs[key] = classname;
            }
            else
            {
                var classname = this.views_defs[key];
            }
            // convert return data to proper objects
            return this.rehydrate( classname, result );
        }
    }
    else
    {
        alert( 'Cannot execute fetch: a webservices extension needs to be enabled (ezjscore or ggwebservices)' );
        /// @todo what if callback is defined, shall we invoke it?
        return;
    }
}

/**
  Fetches the description (return type) of a tpl fetch function via an ajax call to a fetch func exposed
  by ezpersistentobject_inspector

  @todo use 2-level encoding of resultset instead of 1-level? might have some interesting other data...
  @todo use a more specific fetch function?
*/
p.fetchFetchDef = function( module, fetch, callback )
{
    if ( this.ws_transport == 'ezjscore' || this.ws_transport == 'ggwebservices' )
    {
        if ( this.ws_transport == 'ezjscore' )
        {
            var postData = 'ezjscServer_function_arguments=' + 'ezp::fetch::' + 'internaldocumentation' + '::' + 'fetch' + '::' + this.Y.JSON.stringify( { "module": module, "view": fetch } );
        }
        else
        {
            var postData = this.Y.JSON.stringify( { "method": "ezp.fetchall", "params": [ 'internaldocumentation', 'fetch', { "module": module, "view": fetch } ], "id": 1 } );
        }

        var result = this._sendRequest( postData, callback );

        // sync processing
        if ( callback === undefined ) /// @todo test if result is not null / undefined
        {
            /// @todo test that member 'return' is defined
            return result.return;
        }
    }
    else
    {
        alert( 'Cannot fetch definition of fetch function: a webservices extension needs to be enabled (ezjscore or ggwebservices)' );
    }
}

/*
  Fetch definition of an ezpo class via an ajax call to a fetch func exposed
  by ezpersistentobject_inspector
  @todo use a more specific fetch function?
*/
p.fetchObjectDef = function( classname, callback )
{
    if ( this.ws_transport == 'ezjscore' || this.ws_transport == 'ggwebservices' )
    {
        if ( this.ws_transport == 'ezjscore' )
        {
            var postData = 'ezjscServer_function_arguments=' + 'ezp::fetch::' + 'internaldocumentation' + '::' + 'object' + '::' + this.Y.JSON.stringify( { "class": classname } ) + '::[]::2';
        }
        else
        {
            var postData = this.Y.JSON.stringify( { "method": "ezp.fetchall", "params": [ 'internaldocumentation', 'object', { "class": classname }, [], 2 ], "id": 1 } );
        }

        var result = this._sendRequest( postData, callback );

        // sync processing
        if ( callback === undefined ) /// @todo test if result is not null / undefined
        {
            return result;
        }
    }
    else
    {
        alert( 'Cannot fetch object definition: a webservices extension needs to be enabled (ezjscore or ggwebservices)' );
    }
}

/**
 @todo add jquery support besides yui
 @access private
*/
p._sendRequest = function( postData, arguments, callback )
{

        var sync = ( callback === undefined );
        var cfg = {
            "method": "POST",
            "data": postData,
            "sync": sync,
            "arguments": { "client": this } /// @todo merge args from invoker
        }
        if ( !sync )
        {
            /// @todo wrap user callback in own callback
            cfg.callback = callback;
        }

        /*
         * var request will contain the following fields, when the
         * transaction is complete:
         * - id
         * - status
         * - statusText
         * - getResponseHeader()
         * - getAllResponseHeaders()
         * - responseText
         * - responseXML
         * - arguments
         */
        var response = this.Y.io( this.transport_url, cfg );
        if ( sync )
        {
            status = response.status;
            if ( true /* @todo check resp. status */ )
            {
                return this._processResponse( response );
            }
            else
            {
                return null;
            }
        }

}

/**
 @access private
*/
p._processResponse = function( responseObj )
{
    try
    {
        var response = this.Y.JSON.parse( responseObj.responseText );
    }
    catch(e)
    {
        alert( 'Invalid data received from server via ajax call (not json?) ' + responseObj.responseText.substr( 0, 1000 ) );
        return;
    }
    if ( responseObj.arguments.client.ws_transport == 'ezjscore' )
    {
        if ( response.error_text === undefined || response.content === undefined )
        {
            alert( 'Invalid date received from server via ajax call (invalid json structure)' );
            return;
        }
        else if ( response.error_text != "" )
        {
            alert( response.error_text );
            return;
        }
        else
        {
            response = response.content;
        }
    }
    else
    { // ggwebservices
        if ( response.result === undefined || response.error === undefined || response.id === undefined )
        {
            alert( 'Invalid data received from server via ajax call (invalid json structure)' );
            return;
        }
        else if ( response.error != null )
        {
            alert( response.error );
            return;
        }
        else
        {
            response = response.result;
        }
    }

    if ( response === false )
    {
        alert( 'Invalid data received from server via ajax call (empty content)' );
        return;
    }

    return response;
}

/// @todo test more...
p.rehydrate = function( classname, value )
{
    // we need to clean up the syntax used for defining types both
    // . by definition of fetch functions results (eg. 'An array of someclass or NULL'), and
    // . by ezpo dynamic attributes (eg 'array [someclass]')
    classname = classname.replace( /^[Aa]n */, '' ).replace( / +o[rf] +(NULL|FALSE)\.?$/, '' ).replace( / +object$/, '' ).toLowerCase();

    if ( classname == 'string' )
    {
        return value.toString();
    }
    else if ( classname == 'bool' || classname == 'boolean' )
    {
        return value.toBoolean();
    }
    else if ( classname == 'int' || classname == 'integer' )
    {
        return value.toInteger();
    }
    else if ( classname == 'float' )
    {
        return value.toNumber();
    }
    else if ( classname.slice(0, 5) == 'array' )
    {
        if ( value === null )
        {
            return null;
        }
        classname = classname.replace( /^array( +of)? */, '' ).replace( /^[/, '' ).replace( /]$/, '' );
        var out = []
        for (var i = 0; i < value.length; i++)
        {
            out[i] = this.rehydrate( classname, value[i] );
        }
        return out;
    }
    else if ( classname.slice(0, 6) == 'object' )
    {
        classname = classname.replace( /^object */, '' ).replace( /^[/, '' ).replace( /]$/, '' );

        if ( value === null )
        {
            return null;
        }
        // dynamically load definition of 'classname' class
        /// @todo use a better check (to exclude non-prototype values)
        if ( window[classname] === undefined )
        {
            this._loadClassDefinition( classname );
        }
        return new window[classname]( value );
    }
    else if ( classname.slice(0, 4) == 'hash' || classname == 'unknown' )
    {
        return value;
    }
    else
    {
        // not a known type: must be a class name, as returned in fetch function return type descriptions

        if ( value === null )
        {
            return null;
        }
        // dynamically load definition of 'classname' class
        /// @todo use a better check (to exclude non-prototype values)
        if ( window[classname] === undefined )
        {
            this._loadClassDefinition( classname );
        }
        return new window[classname]( value );
    }
}

/**
 Creates dynamically the definition of a javascript class corresponding to an eZPO subclass
 @access private
*/
p._loadClassDefinition = function( classname )
{
    var objDef = this.fetchObjectDef( classname );

    // closure power! ;-)
    var objKey = objDef.keys[0];
    var tplProxy = this;
    var name = classname;

    var constructor =
        function( hash )
        {
            this._key = objKey;
            this._classname = name;
            this._dynattrs = [];
            /// @todo shave a bit of ram here by not saving objDef in all object instances but in prototype?
            this._objdef = objDef;
            this._tplproxy = tplProxy;

            // a hackish way to make sure closures work with getters
            // (taken from http://www.webdeveloper.com/forum/archive/index.php/t-222741.html)
            var that = this;
            function binder( propname )
            {
                that.__defineGetter__( propname, function(){ return this.attribute( propname ); } );
            }

            // use a smart but slow constructor: only valid static attributes are absorbed from the hash
            for ( var attrname in this._objdef.attributes )
            {
                if ( this._objdef.attributes[attrname].static )
                {
                    if ( hash[attrname] !== undefined )
                    {
                        this[attrname] = hash[attrname];
                    }
                    else
                    {
                        this[attrname] = null;
                    }
                }
                else
                {
                    // for dynamic attributes, define a getter method
                    binder( attrname );
                }
            }

        }

    var p = constructor.prototype;

    p.attribute =
        function( attrname )
        {
            /// check 1st if it is a static attr
            if ( this._objdef.attributes[attrname] !== undefined && this._objdef.attributes[attrname].static )
            {
                return this[attrname];
            }
            if ( this._dynattrs[attrname] === undefined )
            {
                this._dynattrs[attrname] = tplProxy._fetchAttribute( this._classname, this[this._key], attrname, this._objdef.attributes[attrname].type );
            }
            return this._dynattrs[attrname];
        }

    window[classname] = constructor;
}

/**
 "almost" private method, since this is used by ezpo classes
*/
p._fetchAttribute = function( classname, key, attribute, type, callback )
{
    if ( this.ws_transport == 'ezjscore' || this.ws_transport == 'ggwebservices' )
    {
        if ( this.ws_transport == 'ezjscore' )
        {
            var postData = 'ezjscServer_function_arguments=' + 'ezp::inspect::' + classname + '::' + key + '::' + attribute;
        }
        else
        {
            var postData = this.Y.JSON.stringify( { "method": "ezp.inspect", "params": [ classname, key, attribute ], "id": 1 } );
        }

        var result = this._sendRequest( postData, callback );

        // sync processing
        if ( callback === undefined )
        {
            /// @todo test result.type vs. type and check that they match
            /// @todo test that result.value exists
            return this.rehydrate( type, result.value );
        }

    }
    else
    {
        alert( 'Cannot drill down for attribute: a webservices extension need to be enabled (ezjscore or ggwebservices)' );
    }
}
