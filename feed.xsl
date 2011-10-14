<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:atom="http://www.w3.org/2005/Atom">
<xsl:output method="html"/>

<xsl:template match="/atom:feed">
    <html>
        <head>
            <title>Atom feed: <xsl:value-of select="atom:title"/></title>
        </head>
        <body>
            <h1>Atom feed: <xsl:value-of select="atom:title"/></h1>
            <p>
              This is an Atom feed. If you use a news reader program or
              website, you can add the Internet address of this page in order
              to be updated when new information is posted to this feed.
            </p>
            <p>
              Your course doesn't require you to use this page but, if you are
              interested, you can find out more about Atom from public websites such as
              <a href="http://atomenabled.org/">Atom Enabled</a>.
            </p>
            <p>
              Click your browser's Back button to return to the website.
            </p>
        </body>
    </html>
</xsl:template>

<xsl:template match="/rss">
    <html>
        <head>
            <title>RSS feed: <xsl:value-of select="channel/title"/></title>
        </head>
        <body>
            <h1>RSS feed: <xsl:value-of select="channel/title"/></h1>
            <p>
              This is an RSS feed. If you use a news reader program or
              website, you can add the Internet address of this page in order
              to be updated when new information is posted to this feed.
            </p>
            <p>
              Your course doesn't require you to use this page but, if you are
              interested, you can find out more about RSS from public sources
              such as
              <a href="http://en.wikipedia.org/wiki/RSS_%28file_format%29">this Wikipedia entry</a>.
            </p>
            <p>
              Click your browser's Back button to return to the website.
            </p>
        </body>
    </html>
</xsl:template>

</xsl:stylesheet>
