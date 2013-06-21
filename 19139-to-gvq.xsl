<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="2.0"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                xmlns:gml="http://www.opengis.net/gml/3.2"
                xmlns:gmi="http://www.isotc211.org/2005/gmi"
                xmlns:gmx="http://www.isotc211.org/2005/gmx"
                xmlns:gco="http://www.isotc211.org/2005/gco"
                xmlns:gmd="http://www.isotc211.org/2005/gmd"
                xmlns:updated19115="http://www.geoviqua.org/19115_updates"
                xmlns:gvq="http://www.geoviqua.org/QualityInformationModel/4.0"
                xmlns:gmd19157="http://www.geoviqua.org/gmd19157"
                xmlns:un="http://www.uncertml.org/2.0"
                exclude-result-prefixes="#all">

    <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
    <!--<xsl:strip-space elements="*"/>-->
    
    <!-- ===============================================
    Begin processing
    ==================================================== -->
    
    <!-- start with root element -->
    <xsl:template match="/">
        <xsl:apply-templates select="gmd:MD_Metadata"/>
    </xsl:template>

    <!-- identity transform -->
    <xsl:template match="@*|node()" name="identity">
        <xsl:copy copy-namespaces="no">
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>
    
    
    
    <!-- ===============================================
    gmd:MD_Metadata to gvq:GVQ_Metadata and update namespaces/schema locations
    ==================================================== -->
    <xsl:template match="gmd:MD_Metadata" priority="200">
        <xsl:element name="gvq:GVQ_Metadata">
            <xsl:namespace name="updated19115" select="'http://www.geoviqua.org/19115_updates'"/>
            <xsl:namespace name="gmx" select="'http://www.isotc211.org/2005/gmx'"/>
            <xsl:namespace name="xlink" select="'http://www.w3.org/1999/xlink'"/>
            <xsl:namespace name="xsi" select="'http://www.w3.org/2001/XMLSchema-instance'"/>
            <xsl:namespace name="gmd" select="'http://www.isotc211.org/2005/gmd'"/>
            <xsl:namespace name="gco" select="'http://www.isotc211.org/2005/gco'"/>
            <xsl:namespace name="gvq" select="'http://www.geoviqua.org/QualityInformationModel/4.0'"/>
            <xsl:namespace name="gml" select="'http://www.opengis.net/gml/3.2'"/>
            <xsl:namespace name="gmd19157" select="'http://www.geoviqua.org/gmd19157'"/>
            <xsl:namespace name="un" select="'http://www.uncertml.org/2.0'"/>
			<xsl:copy-of select="@*[name()!='xsi:schemaLocation']"/>
			<xsl:attribute name="xsi:schemaLocation">
                <xsl:text>http://www.geoviqua.org/QualityInformationModel/4.0 http://schemas.geoviqua.org/GVQ/4.0/GeoViQua_PQM_UQM.xsd </xsl:text>
                <xsl:text>http://www.uncertml.org/2.0 http://www.uncertml.org/uncertml.xsd</xsl:text>
            </xsl:attribute>
            <xsl:apply-templates select="child::*"/>
		</xsl:element>
	</xsl:template>
    
    
    
    <!-- ===============================================
    gmd:MD_DataIdentification to gvq:GVQ_DataIdentification
    ==================================================== -->
    <xsl:template match="gmd:MD_DataIdentification">
        <xsl:element name="gvq:GVQ_DataIdentification">
            <xsl:apply-templates select="@*|node()"/>
            <!-- insert gvq:referenceDoc skeleton -->
            <xsl:call-template name="gvqReferenceDoc"/>
		</xsl:element>
	</xsl:template>
    
    
    
    <!-- ===============================================
    gmd:MD_Identifier to updated19115:MD_Identifier
    ==================================================== -->
    <xsl:template match="gmd:MD_Identifier">
        <xsl:element name="updated19115:MD_Identifier">
            <xsl:apply-templates select="@*|node()"/>
            <!-- add updated19115:codeSpace skeleton -->
            <updated19115:codeSpace gco:nilReason="missing">
                <gco:CharacterString/>
            </updated19115:codeSpace>
		</xsl:element>
	</xsl:template>
    
    
    
    <!-- ===============================================
    gmd:CI_OnlineResource under gmd:MD_DigitalTransferOptions parent to updated19115:CI_OnlineResource
    ==================================================== -->
    <xsl:template match="gmd:MD_DigitalTransferOptions/gmd:onLine/gmd:CI_OnlineResource">
        <xsl:element name="updated19115:CI_OnlineResource">
            <xsl:apply-templates select="@*|node()"/>
            <updated19115:protocolRequest gco:nilReason="missing">
                <gco:CharacterString/>
            </updated19115:protocolRequest>
		</xsl:element>
	</xsl:template>
    
    
    
    <!-- ===============================================
    Insert gmd:describes after gmd:distributionInfo
    ==================================================== -->
    <xsl:template match="gmd:MD_Metadata/gmd:distributionInfo">
        <xsl:call-template name="identity"/>
        <xsl:choose>
            <xsl:when test="/gmd:MD_Metadata/gmd:describes">
                <!-- copy all gmd:describes nodes to the right location -->
                <xsl:copy-of select="/gmd:MD_Metadata/gmd:describes"/>
			</xsl:when>
            <xsl:otherwise>
                <!-- insert gmd:describes skeleton -->
                <xsl:call-template name="gmdDescribes"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
    
    <!-- remove all original gmd:describes nodes after copy -->
    <xsl:template match="gmd:MD_Metadata/gmd:describes"/>
    
    
    
    <!-- ===============================================
    gmd:dataQualityInfo to gvq:dataQualityInfo
    ==================================================== -->
    <xsl:template match="gmd:dataQualityInfo">
        <xsl:element name="gvq:dataQualityInfo">
            <xsl:apply-templates select="@*|node()"/>
		</xsl:element>
	</xsl:template>
    
    <!-- add gmd19157:resultScope to the first child of gmd:result (e.g. gmd:DQ_QuantitativeResult) -->
    <xsl:template match="gmd:report//gmd:result/*[1]" priority="300">
        <xsl:element name="gmd19157:{local-name()}">
            <xsl:copy-of select="@*"/>
            <gmd19157:resultScope xlink:href="#"/>
            <xsl:apply-templates select="child::*"/>
        </xsl:element>
	</xsl:template>
    
    <!-- update the first child of a gmd:report (e.g. gmd:DQ_QuantitativeAttributeAccuracy) to match AbstractDQ_Element_Type's sequence -->
    <xsl:template match="gmd:report/*[1]" priority="400">
        <xsl:element name="gmd19157:{local-name()}">
            <xsl:copy-of select="@*"/>
            
            <!-- move gmd:dateTime -->
            <xsl:apply-templates select="gmd:dateTime"/>
            
            <!-- move into DQ_MeasureReference -->
            <gmd19157:measure>
                <gmd19157:DQ_MeasureReference>
                    <xsl:apply-templates select="gmd:measureIdentification"/>
                    <xsl:apply-templates select="gmd:nameOfMeasure"/>
                    <xsl:apply-templates select="gmd:measureDescription"/>
				</gmd19157:DQ_MeasureReference>
			</gmd19157:measure>
            
            <!-- these elements will be moved into a DQ_EvaluationMethod skeleton -->
            <xsl:variable name="evaluationMethodDescription" select="gmd:evaluationMethodDescription"/>
            <xsl:variable name="evaluationProcedure" select="gmd:evaluationProcedure"/>
            <xsl:variable name="evaluationMethodType" select="gmd:evaluationMethodType"/>
            
            <gmd19157:evaluation>
                <!-- insert gvq:GVQ_SampleBasedInspection as a placeholder for now,
                     can template additional DQ_EvaluationMethod types and extend this
                     to test the above evaluation variables and call the most suitable template -->
                <xsl:call-template name="gvqGVQ_SampleBasedInspection">
                    <xsl:with-param name="evaluationMethodDescription" select="$evaluationMethodDescription"/>
                    <xsl:with-param name="evaluationProcedure" select="$evaluationProcedure"/>
                    <xsl:with-param name="evaluationMethodType" select="$evaluationMethodType"/>
				</xsl:call-template>
			</gmd19157:evaluation>
            
            <!-- copy all child nodes apart from those we have "moved" -->
            <xsl:apply-templates select="child::*[not(self::gmd:dateTime|
                                                      self::gmd:evaluationMethodDescription|self::gmd:evaluationProcedure|self::gmd:evaluationMethodType|
                                                      self::gmd:measureIdentification|self::gmd:nameOfMeasure|self::gmd:measureDescription
                                                    )]"/>
		</xsl:element>
	</xsl:template>
    
    <!-- rename gmd prefix to gmd19157 where appropriate (ancestors that contain original gmd types should not have their child elements renamed) -->
    <xsl:template name="gmd_to_gmd19157" match="gmd:dataQualityInfo//gmd:*[not(ancestor-or-self::gmd:MD_Identifier|
                                                                               ancestor::gmd:processor|
                                                                               ancestor::gmd:scaleDenominator|
                                                                               ancestor::gmd:sourceReferenceSystem|
                                                                               ancestor::gmd:sourceCitation|
                                                                               ancestor::gmd:sourceExtent|
                                                                               ancestor::gmd:spatialRepresentationType|
                                                                               ancestor::gmd:*[parent::gmd:DQ_Scope]|
                                                                               ancestor::gmi:resultFile|
                                                                               ancestor::gmi:resultSpatialRepresentation|
                                                                               ancestor::gmi:resultContentDescription|
                                                                               ancestor::gmi:resultFormat
                                                                            )]" priority="200">
        <xsl:element name="gmd19157:{local-name()}">
            <xsl:apply-templates select="@*|node()"/>
        </xsl:element>
	</xsl:template>
    
    
    
    <!-- ===============================================
    Skeletons for required/optional elements
    ==================================================== -->
    
    <!-- gvq:GVQ_SampleBasedInspection skeleton -->
    <xsl:template name="gvqGVQ_SampleBasedInspection">
        <xsl:param name="evaluationMethodDescription"/>
        <xsl:param name="evaluationProcedure"/>
        <xsl:param name="evaluationMethodType"/>
        
        <gvq:GVQ_SampleBasedInspection>
            <xsl:apply-templates select="$evaluationMethodDescription"/>
            <xsl:apply-templates select="$evaluationProcedure"/>
            <gmd19157:referenceDoc xlink:href="#"/>
            <xsl:apply-templates select="$evaluationMethodType"/>
            <gmd19157:samplingScheme gco:nilReason="missing">
                <gco:CharacterString/>
            </gmd19157:samplingScheme>
            <gmd19157:lotDescription gco:nilReason="missing">
                <gco:CharacterString/>
            </gmd19157:lotDescription>
            <gmd19157:samplingRatio gco:nilReason="missing">
                <gco:CharacterString/>
            </gmd19157:samplingRatio>
        </gvq:GVQ_SampleBasedInspection>
	</xsl:template>
    
    <!-- gvq:referenceDoc skeleton -->
    <xsl:template name="gvqReferenceDoc">
        <gvq:referenceDoc>
			<gvq:GVQ_Publication>
				<gmd:title gco:nilReason="missing">
					<gco:CharacterString/>
				</gmd:title>
				<gmd:date>
					<gmd:CI_Date>
						<gmd:date>
							<gco:Date>1900</gco:Date>
						</gmd:date>
						<gmd:dateType>
							<gmd:CI_DateTypeCode codeList="http://www.isotc211.org/2005/resources/Codelist/gmxCodelists.xml#CI_DateTypeCode" codeListValue=""/>
						</gmd:dateType>
					</gmd:CI_Date>
				</gmd:date>
				<gmd:citedResponsibleParty xlink:href="#"/>
				<gmd:series>
					<gmd:CI_Series>
						<gmd:name gco:nilReason="missing">
							<gco:CharacterString/>
						</gmd:name>
						<gmd:issueIdentification gco:nilReason="missing">
							<gco:CharacterString/>
						</gmd:issueIdentification>
						<gmd:page gco:nilReason="missing">
							<gco:CharacterString/>
						</gmd:page>
					</gmd:CI_Series>
				</gmd:series>
				<gmd:ISSN gco:nilReason="missing">
					<gco:CharacterString/>
				</gmd:ISSN>
				<gvq:target xlink:href="#"/>
				<gvq:doi gco:nilReason="missing">
					<gco:CharacterString/>
				</gvq:doi>
				<gvq:purpose>
					<gvq:GVQ_PublicationPurposeCode codeList="http://schemas.geoviqua.org/GVQ/4.0/resources/Codelist/gvqCodelists.xml#GVQ_PublicationPurposeCode" codeListValue=""/>
				</gvq:purpose>
				<gvq:scope xlink:href="#"/>
				<gvq:category>
					<gvq:GVQ_PublicationCategoryCode codeList="http://schemas.geoviqua.org/GVQ/4.0/resources/Codelist/gvqCodelists.xml#GVQ_PublicationCategoryCode" codeListValue=""/>
				</gvq:category>
				<gvq:onlineResource>
					<gmd:CI_OnlineResource>
						<gmd:linkage>
							<gmd:URL/>
						</gmd:linkage>
					</gmd:CI_OnlineResource>
				</gvq:onlineResource>
			</gvq:GVQ_Publication>
		</gvq:referenceDoc>
	</xsl:template>
    
    <!-- gmd:describes skeleton -->
    <xsl:template name="gmdDescribes">
    	<gmd:describes>
    		<gmx:MX_DataSet>
    			<gmd:has xlink:href="#"/>
    			<gmx:dataFile>
    				<gmx:MX_DataFile>
    					<gmx:fileName>
    						<gmx:FileName/>
    					</gmx:fileName>
    					<gmx:fileDescription gco:nilReason="missing">
    						<gco:CharacterString/>
    					</gmx:fileDescription>
    					<gmx:fileType>
    						<gmx:MimeFileType type=""/>
    					</gmx:fileType>
    					<gmx:featureTypes gco:nilReason="missing">
    						<gco:LocalName/>
    					</gmx:featureTypes>
    					<gmx:fileFormat>
    						<gmd:MD_Format>
    							<gmd:name gco:nilReason="missing">
    								<gco:CharacterString/>
    							</gmd:name>
    							<gmd:version gco:nilReason="missing">
    								<gco:CharacterString/>
    							</gmd:version>
    							<gmd:fileDecompressionTechnique gco:nilReason="missing">
    								<gco:CharacterString/>
    							</gmd:fileDecompressionTechnique>
    						</gmd:MD_Format>
    					</gmx:fileFormat>
    				</gmx:MX_DataFile>
    			</gmx:dataFile>
    		</gmx:MX_DataSet>
    	</gmd:describes>
	</xsl:template>
    
</xsl:stylesheet>
