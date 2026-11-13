allprojects {
    repositories {
        google()
        mavenCentral()
    }
}

subprojects {
    project.evaluationDependsOn(":app")
}

subprojects {
    if (project.name == "mollie_flutter") {
        afterEvaluate {
            if (project.plugins.hasPlugin("com.android.library")) {
                val android = project.extensions.getByType(com.android.build.gradle.BaseExtension::class.java)
                android.namespace = "com.plugon.mollie"
            }
        }
    }
}

tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}
