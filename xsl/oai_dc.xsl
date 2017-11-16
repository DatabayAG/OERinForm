<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:dc="http://purl.org/dc/elements/1.1/">
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

<!--  Basic rule: copy everything not specified and process the childs -->
<xsl:template match="@*|node()">
    <xsl:copy><xsl:apply-templates select="@*|node()" /></xsl:copy>
</xsl:template>

<!--
   Main transformations
-->
    <xsl:template match="MetaData">
        <oai_dc:dc
                xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">

            <dc:title>
                <xsl:value-of select="//General/Title" />
            </dc:title>
            <xsl:apply-templates select="//Lifecycle/Contribute[@Role='Author']/Entity" mode="creator" />
            <xsl:apply-templates select="//General/Keyword" mode="subject"/>
            <dc:description>
                <xsl:value-of select="//Description" />
            </dc:description>
            <xsl:apply-templates select="//Lifecycle/Contribute[@Role='Publisher']/Entity" mode="publisher" />
            <xsl:apply-templates select="//Lifecycle/Contribute[@Role!='Publisher' and @Role!='Author']/Entity" mode="contributor" />
            <dc:date>
                <xsl:value-of select="//Lifecycle/Contribute[@Role='Publisher']/Date" />
            </dc:date>
            <dc:type>
                <xsl:value-of select="//Educational/@LearningResourceType" />
            </dc:type>
            <dc:format>
                <xsl:value-of select="//Technical/Format/text()" />
            </dc:format>
            <dc:identifier>
                <xsl:value-of select="//General/Identifier[@Catalog='ILIAS']/@Entry" />
            </dc:identifier>
            <dc:source>
                {ILIAS_URL}
                <!--<xsl:value-of select="//Relation[@Kind='IsBasedOn']/Resource/Description" />-->
            </dc:source>
            <dc:language>
                <xsl:value-of select="//General/Language/@Language" />
            </dc:language>
            <xsl:apply-templates select="//Relation[@Kind!='IsBasedOn']/Resource/Description" mode="relation" />
            <dc:coverage>
                <xsl:value-of select="//General/Coverage" />
            </dc:coverage>
            <dc:rights>
                <xsl:value-of select="//Rights/Description" />
            </dc:rights>
        </oai_dc:dc>
    </xsl:template>

     <xsl:template match="Entity" mode="creator">
        <dc:creator>
            <xsl:value-of select="." />
        </dc:creator>
    </xsl:template>

    <xsl:template match="Entity" mode="publisher">
        <dc:publisher>
            <xsl:value-of select="." />
        </dc:publisher>
    </xsl:template>

    <xsl:template match="Entity" mode="contributor">
        <dc:contributor>
            <xsl:value-of select="." />
        </dc:contributor>
    </xsl:template>

    <xsl:template match="Keyword" mode="subject">
        <dc:subject>
            <xsl:value-of select="." />
        </dc:subject>
    </xsl:template>

    <xsl:template match="Description" mode="relation">
        <dc:relation>
            <xsl:value-of select="." />
        </dc:relation>
    </xsl:template>


</xsl:stylesheet>