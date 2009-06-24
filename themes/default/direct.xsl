<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xhtml="http://www.w3.org/1999/xhtml"
    xmlns="http://www.w3.org/1999/xhtml" xmlns:i18n="http://apache.org/cocoon/i18n/2.1"
    exclude-result-prefixes="xhtml i18n" version="1.0">

    <xsl:template match="/">
        <html lang="{$lang}" xml:lang="{$lang}">
            <head>
                <meta http-equiv="content-type" content="text/html; charset=utf-8" />
                <meta name="robots" content="NONE,NOARCHIVE" />
                <title>
                    <i18n:text>PageTitle</i18n:text>
                </title>
                <link rel="stylesheet" type="text/css"
                    href="{concat($webrootStatic, 'stylesheets/screen.css')}" />
            </head>

           


            <body>
                <div id="summary">
                    <h1>
                       Direct Response Page
                    </h1>
                </div>
   <div id="instructions">
                    
                    <ul>
                    
                    
                    <xsl:for-each select="/command/*">
                        <li>
                            <xsl:value-of select="local-name()"/>  => <xsl:value-of select="."/>  
                        </li>
</xsl:for-each>
                        </ul>
                </div>
            
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
