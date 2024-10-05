<div class="json-wrapper">
  <div class="json-header">
    <div class="json-brand">
      <div class="logo"><span>SJE</span></div>
      <h1 class="titre">Simple JSON Editor</h1>
    </div>
    
    <div class="actions">
      <div>
        <label for="folder-select">Select a folder&nbsp;</label>
        <select id="folder-select">
          <option value="">-- Select a folder --</option>
        </select>
      </div>
      <button id="save-json" class="button button-primary button-save">Save Changes</button>
    </div>
  </div>
  
  <div class="json-grid">
    <div id="json-file-list" class="json-list-files">
      <!-- La liste des fichiers JSON sera injectée ici -->
    </div>
    <div id="json-editor-infos" class="json-editor-infos">
      Select a directory in the top selector. Only directories containing json files are displayed.<br>Then click on the desired file in the left-hand column to edit it.
    </div>
    <div class="json-editor-container" style="display:none;">
      <div id="ace-editor" class="code-editor"></div>
    </div>
  </div>
</div>