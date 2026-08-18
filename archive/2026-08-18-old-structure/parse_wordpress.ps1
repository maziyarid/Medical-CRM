param (
    [string]$xmlPath,
    [string]$jsonPath
)

try {
    # Read the XML file content
    $xmlContent = Get-Content -Path $xmlPath -Raw

    # Create an XML object
    $xml = [xml]$xmlContent

    # Select all 'item' nodes
    $items = $xml.rss.channel.item

    $posts = @()

    foreach ($item in $items) {
        $postType = $item.get_Item("post_type").'#cdata-section'
        if ($postType -eq 'post') {
            $title = $item.title
            $postName = $item.get_Item("post_name").'#cdata-section'
            $pubDate = $item.get_Item("post_date_gmt").'#cdata-section'
            $content = $item.get_Item("encoded").'#cdata-section'

            # Look for Elementor data if content is empty
            if ([string]::IsNullOrWhiteSpace($content)) {
                $postMeta = $item.postmeta | Where-Object { $_.meta_key -eq '_elementor_data' }
                if ($postMeta) {
                    $elementorData = $postMeta.meta_value
                    # This is a JSON string inside an XML CDATA, so it needs to be unescaped
                    $elementorJson = [System.Web.HttpUtility]::HtmlDecode($elementorData)
                    $elementorObject = $elementorJson | ConvertFrom-Json
                    
                    $generatedContent = ""
                    foreach($element in $elementorObject){
                        foreach($widget in $element.elements){
                            if($widget.widgetType -eq 'text-editor'){
                                $generatedContent += $widget.settings.editor
                            }
                        }
                    }
                    $content = $generatedContent
                }
            }


            $posts += [PSCustomObject]@{
                Title      = $title
                PostName   = $postName
                PubDate    = $pubDate
                Content    = $content
            }
        }
    }

    # Convert the array of custom objects to a JSON string
    $jsonOutput = $posts | ConvertTo-Json -Depth 10

    # Write the JSON string to the specified file
    Set-Content -Path $jsonPath -Value $jsonOutput -Encoding UTF8
    
    Write-Host "Successfully parsed XML and created JSON file at $jsonPath"

}
catch {
    Write-Error "An error occurred: $_"
    exit 1
}
