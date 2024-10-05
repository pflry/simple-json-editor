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
    <div class="json-editor-container" style="display:none;">
      <div id="ace-editor" class="code-editor"></div>
    </div>
  </div>
</div>