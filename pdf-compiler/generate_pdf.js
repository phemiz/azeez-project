const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

(async () => {
  try {
    // 1. Read Markdown file
    const mdPath = 'C:/Users/Hp/.gemini/antigravity/brain/27e8d3b4-840a-4c32-bc1a-020300b273fb/final_aligned_project_document.md';
    let md = fs.readFileSync(mdPath, 'utf8');
    
    // Convert markdown to HTML using marked
    const { marked } = require('marked');
    const htmlContent = marked.parse(md);
    
    // Create HTML wrapping with Times New Roman and standard academic styling
    const fullHtml = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>DESIGN AND IMPLEMENTATION OF A CYBERSECURITY-BASED GSM DATA PROTECTION SYSTEM</title>
      <script src="https://cdn.tailwindcss.com"></script>
      <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ 
          startOnLoad: true, 
          theme: 'neutral',
          themeVariables: {
            background: '#ffffff',
            primaryColor: '#ffffff',
            edgeColor: '#000000',
            textColor: '#000000',
            nodeBorder: '#000000'
          }
        });
      </script>
      <style>
        @page {
          size: A4;
          margin: 25mm 20mm 20mm 20mm;
        }
        body {
          font-family: 'Times New Roman', Times, serif;
          line-height: 1.6;
          color: #000000;
          background-color: #ffffff;
          padding: 0;
          margin: 0;
        }
        h1, h2, h3, h4 {
          font-family: Arial, sans-serif;
          color: #000000;
          page-break-after: avoid;
        }
        h1 { font-size: 22pt; margin-top: 24pt; margin-bottom: 12pt; text-align: center; font-weight: bold; }
        h2 { font-size: 16pt; margin-top: 20pt; margin-bottom: 10pt; border-bottom: 1px solid #000; padding-bottom: 3px; font-weight: bold; }
        h3 { font-size: 13pt; margin-top: 14pt; margin-bottom: 6pt; font-weight: bold; }
        p { font-size: 12pt; margin-bottom: 10pt; text-align: justify; text-indent: 0.5in; }
        p:first-of-type { text-indent: 0; }
        li { font-size: 12pt; }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 12pt;
          margin-bottom: 12pt;
          font-size: 11pt;
          page-break-inside: avoid;
        }
        th, td {
          border: 1px solid #000000;
          padding: 6px 10px;
          text-align: left;
        }
        th {
          background-color: #f3f4f6;
          font-weight: bold;
        }
        pre {
          background-color: #ffffff !important;
          border: none !important;
          padding: 0 !important;
          margin: 15px 0 !important;
          page-break-inside: avoid;
        }
        code {
          font-family: monospace;
          background: none !important;
          padding: 0 !important;
        }
        .page-break {
          page-break-before: always;
        }
        /* Custom page break rules for chapters */
        hr {
          border: none;
          page-break-after: always;
          height: 0;
          margin: 0;
        }
      </style>
    </head>
    <body>
      <div style="padding: 10px;">
        ${htmlContent}
      </div>
    </body>
    </html>
    `;
    
    const tempHtmlPath = path.join(__dirname, 'temp.html');
    fs.writeFileSync(tempHtmlPath, fullHtml);
    console.log('HTML compilation complete.');
    
    // 2. Launch headless browser using system's Google Chrome
    const browser = await puppeteer.launch({
      executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const page = await browser.newPage();
    
    // Load local compiled HTML
    const fileUrl = 'file://' + tempHtmlPath;
    await page.goto(fileUrl, { waitUntil: 'load', timeout: 60000 });
    
    // Wait for Mermaid script to load and compile SVGs
    await page.evaluate(async () => {
      if (document.querySelector('.mermaid')) {
        await new Promise(resolve => setTimeout(resolve, 5000));
      }
    });
    
    // 3. Print page to PDF
    const pdfOutputPath = 'C:/Users/Hp/.gemini/antigravity/brain/27e8d3b4-840a-4c32-bc1a-020300b273fb/final_aligned_project_document.pdf';
    await page.pdf({
      path: pdfOutputPath,
      format: 'A4',
      margin: {
        top: '25mm',
        bottom: '20mm',
        left: '20mm',
        right: '20mm'
      },
      displayHeaderFooter: true,
      headerTemplate: '<span></span>',
      footerTemplate: '<div style="font-size: 10px; width: 100%; text-align: center; font-family: \'Times New Roman\';"><span class="pageNumber"></span></div>'
    });
    
    await browser.close();
    
    // Clean up temporary HTML file
    if (fs.existsSync(tempHtmlPath)) {
      fs.unlinkSync(tempHtmlPath);
    }
    
    console.log('PDF successfully created at: ' + pdfOutputPath);
    
    // Copy generated PDF to project root directory
    const projectRootPdfPath = 'c:/xampp/htdocs/gsm-security/final_aligned_project_document.pdf';
    fs.copyFileSync(pdfOutputPath, projectRootPdfPath);
    console.log('PDF successfully copied to project root: ' + projectRootPdfPath);
  } catch (error) {
    console.error('Error generating PDF:', error);
    process.exit(1);
  }
})();
