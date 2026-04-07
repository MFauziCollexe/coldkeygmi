Option Explicit

Dim shell
Dim fso
Dim scriptDir
Dim runnerPath
Dim command

Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
runnerPath = fso.BuildPath(scriptDir, "run-laravel-scheduler.bat")

command = """" & runnerPath & """"
shell.Run command, 0, False
