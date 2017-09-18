<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:lom="http://ltsc.ieee.org/xsd/LOM" >
<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>

    <!--  Basic rule: copy everything not specified and process the childs -->
    <xsl:template match="@*|node()">
        <xsl:copy><xsl:apply-templates select="@*|node()" /></xsl:copy>
    </xsl:template>

    <!--
       Main transformations
    -->
    <xsl:template match="MetaData">
        <lom xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:schemaLocation="http://ltsc.ieee.org/xsd/LOM  http://ltsc.ieee.org/xsd/lomv1.0/lom.xsd">

            <general>
                <xsl:apply-templates select="//General/Identifier" mode="identfier"/>
                <title>
                    <string>
                        <xsl:attribute name="language"><xsl:value-of select="//General/Title/@Language" /></xsl:attribute>
                        <xsl:value-of select="//General/Title" />
                    </string>
                </title>
                <description>
                    <string>
                        <xsl:attribute name="language"><xsl:value-of select="//General/Description/@Language" /></xsl:attribute>
                        <xsl:value-of select="//General/Description" />
                    </string>
                </description>

                <language><xsl:value-of select="//General/Language/@Language" /></language>

                <educational>
                    <learningResourceType>
                        <source>ILIAS</source>
                        <value> <xsl:value-of select="//Educational/@LearningResourceType" /></value>
                    </learningResourceType>
                </educational>

                <technical>
                    <location>{ILIAS_URL}</location>
                </technical>
            </general>


        </lom>

    </xsl:template>



    <xsl:template match="Identifier" mode="identfier">
        <identifier>
            <catalog><xsl:value-of select="@Catalog" /></catalog>
            <entry><xsl:value-of select="@Entry" /></entry>
        </identifier>
    </xsl:template>



</xsl:stylesheet>